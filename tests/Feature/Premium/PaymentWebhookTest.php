<?php

use App\Contracts\PaymentWebhookServiceInterface;
use App\Enums\OrderStatus;
use App\Jobs\ProcessMidtransNotification;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

const SERVER_KEY = 'SB-Mid-server-test';

beforeEach(function () {
    config(['services.midtrans.server_key' => SERVER_KEY]);
    $this->user = User::factory()->create();
});

/**
 * Build a signed Midtrans notification for an order.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function notification(Order $order, string $status = 'settlement', array $overrides = []): array
{
    $payload = array_merge([
        'order_id' => $order->order_id,
        'status_code' => '200',
        'gross_amount' => number_format($order->amount, 2, '.', ''),
        'transaction_status' => $status,
        'transaction_id' => 'trx-'.$order->order_id,
        'payment_type' => 'qris',
    ], $overrides);

    $payload['signature_key'] ??= hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].SERVER_KEY);

    return $payload;
}

function orderFor(User $user, string $code = 'pro_12bln', int $amount = 149000): Order
{
    return Order::factory()->create([
        'user_id' => $user->id,
        'product_id' => Product::query()->where('code', $code)->value('id'),
        'amount' => $amount,
    ]);
}

test('a settlement marks the order paid and activates Pro for 365 days', function () {
    $order = orderFor($this->user);

    $this->postJson(route('payments.midtrans.notification'), notification($order))->assertOk();

    $order->refresh();
    $subscription = Subscription::query()->where('user_id', $this->user->id)->first();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->payment_type)->toBe('qris')
        ->and($order->paid_at)->not->toBeNull()
        ->and($subscription->plan)->toBe('pro_12bln')
        ->and((int) round(now()->floatDiffInDays($subscription->active_until)))->toBe(365)
        ->and($this->user->fresh()->hasPro())->toBeTrue();
});

test('the same notification sent twice does not extend Pro twice', function () {
    $order = orderFor($this->user);
    $payload = notification($order);

    $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
    $first = Subscription::query()->first()->active_until;

    $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();

    expect(Subscription::query()->count())->toBe(1)
        ->and(Subscription::query()->first()->active_until->equalTo($first))->toBeTrue();
});

test('a fake signature is rejected and nothing changes', function () {
    $order = orderFor($this->user);

    $this->postJson(route('payments.midtrans.notification'), notification($order, 'settlement', ['signature_key' => 'palsu']))
        ->assertForbidden();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(Subscription::query()->count())->toBe(0);
});

test('an amount that differs from the order is rejected', function () {
    $order = orderFor($this->user);
    $payload = notification($order, 'settlement', ['gross_amount' => '1000.00']);
    $payload['signature_key'] = hash('sha512', $payload['order_id'].'200'.'1000.00'.SERVER_KEY);

    $this->postJson(route('payments.midtrans.notification'), $payload)->assertForbidden();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(Subscription::query()->count())->toBe(0);
});

test('pending keeps the order waiting and expire or deny end it without giving access', function (string $midtrans_status, OrderStatus $expected) {
    $order = orderFor($this->user);

    $this->postJson(route('payments.midtrans.notification'), notification($order, $midtrans_status))->assertOk();

    expect($order->fresh()->status)->toBe($expected)
        ->and($this->user->fresh()->hasPro())->toBeFalse();
})->with([
    'pending' => ['pending', OrderStatus::Pending],
    'expire' => ['expire', OrderStatus::Expired],
    'deny' => ['deny', OrderStatus::Failed],
    'cancel' => ['cancel', OrderStatus::Failed],
]);

test('a card capture counts as paid only when fraud status is accept', function () {
    $challenged = orderFor($this->user);
    $this->postJson(route('payments.midtrans.notification'), notification($challenged, 'capture', ['fraud_status' => 'challenge']))->assertOk();
    expect($challenged->fresh()->status)->toBe(OrderStatus::Pending);

    $accepted = orderFor($this->user);
    $this->postJson(route('payments.midtrans.notification'), notification($accepted, 'capture', ['fraud_status' => 'accept']))->assertOk();
    expect($accepted->fresh()->status)->toBe(OrderStatus::Paid);
});

test('buying while Pro is still active continues from the current end date', function () {
    $end = now()->addDays(10)->startOfSecond();
    Subscription::query()->create(['user_id' => $this->user->id, 'plan' => 'pro_1bln', 'active_until' => $end]);

    $order = orderFor($this->user, 'pro_1bln', 50000);
    $this->postJson(route('payments.midtrans.notification'), notification($order))->assertOk();

    expect(Subscription::query()->first()->active_until->equalTo($end->copy()->addDays(30)))->toBeTrue();
});

test('Pro that has ended falls back to Gratis and keeps the row', function () {
    Subscription::query()->create(['user_id' => $this->user->id, 'plan' => 'pro_1bln', 'active_until' => now()->subDay()]);

    expect($this->user->fresh()->hasPro())->toBeFalse()
        ->and(Subscription::query()->count())->toBe(1);
});

test('every accepted notification is stored raw for audit', function () {
    $order = orderFor($this->user);

    $this->postJson(route('payments.midtrans.notification'), notification($order, 'pending'))->assertOk();

    expect(PaymentEvent::query()->where('order_id', $order->order_id)->count())->toBe(1)
        ->and(PaymentEvent::query()->first()->payload['transaction_status'])->toBe('pending');
});

test('a notification for an unknown order is acknowledged without side effects', function () {
    $order = orderFor($this->user);
    $order->order_id = 'SKL-TIDAK-ADA';

    $this->postJson(route('payments.midtrans.notification'), notification($order))->assertOk();

    expect(Subscription::query()->count())->toBe(0);
});

test('the webhook only stores and queues; the order changes inside the job on the payments queue', function () {
    Queue::fake();
    $order = orderFor($this->user);

    $this->postJson(route('payments.midtrans.notification'), notification($order))->assertOk();

    Queue::assertPushedOn('payments', ProcessMidtransNotification::class);
    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(PaymentEvent::query()->first()->processed_at)->toBeNull();
});

test('a fake signature or wrong amount never reaches the queue', function () {
    Queue::fake();
    $order = orderFor($this->user);

    $this->postJson(route('payments.midtrans.notification'), notification($order, 'settlement', ['signature_key' => 'palsu']))->assertForbidden();

    Queue::assertNothingPushed();
    expect(PaymentEvent::query()->count())->toBe(0);
});

test('running the job applies the payment and marks the event processed', function () {
    Queue::fake();
    $order = orderFor($this->user);
    $this->postJson(route('payments.midtrans.notification'), notification($order))->assertOk();

    $event = PaymentEvent::query()->first();
    (new ProcessMidtransNotification($event->id))->handle(app(PaymentWebhookServiceInterface::class));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($event->fresh()->processed_at)->not->toBeNull();
});

test('the stuck-payment command queues again only old unprocessed events', function () {
    Queue::fake();
    $order = orderFor($this->user);
    $old = PaymentEvent::query()->create(['order_id' => $order->order_id, 'payload' => ['x' => 1]]);
    $old->forceFill(['created_at' => now()->subMinutes(10)])->save();
    PaymentEvent::query()->create(['order_id' => $order->order_id, 'payload' => ['x' => 2]]);
    PaymentEvent::query()->create(['order_id' => $order->order_id, 'payload' => ['x' => 3], 'processed_at' => now()->subMinutes(20)])
        ->forceFill(['created_at' => now()->subMinutes(30)])->save();

    $this->artisan('payments:requeue-stuck')->assertSuccessful();

    Queue::assertPushed(ProcessMidtransNotification::class, 1);
});

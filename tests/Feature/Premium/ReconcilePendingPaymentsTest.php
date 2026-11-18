<?php

use App\Enums\OrderStatus;
use App\Jobs\ProcessMidtransNotification;
use App\Livewire\Premium\Status;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'pricing.enabled' => true,
        'services.midtrans.server_key' => 'SB-Mid-server-test',
        'services.midtrans.is_production' => false,
    ]);
    $this->user = User::factory()->create();
});

/**
 * Create a pending order created the given number of hours ago.
 */
function pendingOrderAged(User $user, int $hours): Order
{
    $order = Order::factory()->create(['user_id' => $user->id]);
    $order->forceFill(['created_at' => now()->subHours($hours)])->save();

    return $order;
}

/**
 * Build a Midtrans transaction status body for an order.
 *
 * @return array<string, mixed>
 */
function midtransStatusBody(Order $order, string $status, ?int $amount = null): array
{
    return [
        'status_code' => $status === 'pending' ? '201' : '200',
        'order_id' => $order->order_id,
        'gross_amount' => number_format($amount ?? $order->amount, 2, '.', ''),
        'transaction_status' => $status,
        'transaction_id' => 'trx-'.$order->order_id,
        'payment_type' => 'bank_transfer',
    ];
}

test('an old order with no payment at Midtrans expires and its status page stops waiting', function () {
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(['status_code' => '404', 'status_message' => "Transaction doesn't exist."], 404)]);
    $order = pendingOrderAged($this->user, 26);

    $this->artisan('payments:reconcile-pending')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Expired)
        ->and(Subscription::query()->count())->toBe(0);

    Livewire::actingAs($this->user)
        ->test(Status::class, ['order' => $order])
        ->assertSee('Pembayaran belum berhasil')
        ->assertDontSee('Menunggu pembayaran');
});

test('Midtrans answering 200 with status code 404 also counts as never paid', function () {
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(['status_code' => '404'], 200)]);
    $order = pendingOrderAged($this->user, 30);

    $this->artisan('payments:reconcile-pending')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Expired);
});

test('a young pending order is left alone and Midtrans is not asked', function () {
    Http::fake();
    $order = pendingOrderAged($this->user, 3);

    $this->artisan('payments:reconcile-pending')->assertSuccessful();

    Http::assertNothingSent();
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('a missed settlement is applied through the payments queue and activates Pro', function () {
    $order = pendingOrderAged($this->user, 26);
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(midtransStatusBody($order, 'settlement'))]);

    $this->artisan('payments:reconcile-pending')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and(PaymentEvent::query()->where('order_id', $order->order_id)->value('processed_at'))->not->toBeNull()
        ->and(Subscription::query()->where('user_id', $this->user->id)->exists())->toBeTrue();
});

test('a final status waiting in the queue is not stored twice on the next run', function () {
    Queue::fake();
    $order = pendingOrderAged($this->user, 26);
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(midtransStatusBody($order, 'expire'))]);

    $this->artisan('payments:reconcile-pending')->assertSuccessful();
    $this->artisan('payments:reconcile-pending')->assertSuccessful();

    Queue::assertPushedOn('payments', ProcessMidtransNotification::class);
    expect(PaymentEvent::query()->count())->toBe(1);
});

test('a payment still in progress at Midtrans stays pending', function () {
    $order = pendingOrderAged($this->user, 26);
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(midtransStatusBody($order, 'pending'))]);

    $this->artisan('payments:reconcile-pending')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(PaymentEvent::query()->count())->toBe(0);
});

test('a wrong amount or an unreachable Midtrans changes nothing and the run reports failure', function (int $http_status, ?int $amount) {
    $order = pendingOrderAged($this->user, 26);
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(midtransStatusBody($order, 'settlement', $amount), $http_status)]);

    $this->artisan('payments:reconcile-pending')->assertFailed();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(PaymentEvent::query()->count())->toBe(0)
        ->and(Subscription::query()->count())->toBe(0);
})->with([
    'amount differs' => [200, 1000],
    'Midtrans error' => [500, null],
    'wrong key' => [401, null],
]);

test('paid and failed orders are never checked again', function () {
    Http::fake();
    $paid = Order::factory()->paid()->create(['user_id' => $this->user->id]);
    $failed = Order::factory()->create(['user_id' => $this->user->id, 'status' => OrderStatus::Failed]);
    Order::query()->whereKey([$paid->id, $failed->id])->update(['created_at' => now()->subDays(3)]);

    $this->artisan('payments:reconcile-pending')->assertSuccessful();

    Http::assertNothingSent();
    expect($paid->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($failed->fresh()->status)->toBe(OrderStatus::Failed);
});

test('Cek status pembayaran applies a payment Midtrans already settled without waiting for the queue', function () {
    Queue::fake();
    $order = pendingOrderAged($this->user, 0);
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(midtransStatusBody($order, 'settlement'))]);

    Livewire::actingAs($this->user)
        ->test(Status::class, ['order' => $order])
        ->assertSee('Menunggu pembayaran')
        ->call('checkStatus')
        ->assertSee('Pembayaran berhasil');

    Queue::assertNothingPushed();
    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($this->user->fresh()->hasPro())->toBeTrue()
        ->and(PaymentEvent::query()->where('order_id', $order->order_id)->value('processed_at'))->not->toBeNull();
});

test('Cek status pembayaran keeps a young unpaid order waiting and never expires it', function () {
    $order = pendingOrderAged($this->user, 0);
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(['status_code' => '404'], 200)]);

    Livewire::actingAs($this->user)
        ->test(Status::class, ['order' => $order])
        ->call('checkStatus')
        ->assertSee('Pembayaranmu belum masuk.');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('Cek status pembayaran can be clicked once per cooldown and shows a friendly error when Midtrans is down', function () {
    $order = pendingOrderAged($this->user, 0);
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response([], 500)]);

    Livewire::actingAs($this->user)
        ->test(Status::class, ['order' => $order])
        ->call('checkStatus')
        ->assertSee('Status belum bisa dicek sekarang.')
        ->call('checkStatus')
        ->assertSee('sebelum cek lagi.');

    Http::assertSentCount(1);
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('Cek status pembayaran refuses a Midtrans amount that differs from the order', function () {
    $order = pendingOrderAged($this->user, 0);
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(midtransStatusBody($order, 'settlement', 1000))]);

    Livewire::actingAs($this->user)
        ->test(Status::class, ['order' => $order])
        ->call('checkStatus')
        ->assertSee('Status belum bisa dicek sekarang.');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($this->user->fresh()->hasPro())->toBeFalse();
});

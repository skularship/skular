<?php

use App\Contracts\CheckoutServiceInterface;
use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Livewire\Premium\Index;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'pricing.enabled' => true,
        'pricing.launching_until' => now()->addDays(10)->toIso8601String(),
        'services.midtrans.server_key' => 'SB-Mid-server-test',
        'services.midtrans.client_key' => 'SB-Mid-client-test',
        'services.midtrans.is_production' => false,
    ]);
});

/**
 * Fake a working Midtrans Snap endpoint. Call it only in tests that need it:
 * the first matching fake wins, so a test cannot override it later.
 */
function fakeMidtrans(): void
{
    Http::fake([
        'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(['token' => 'snap-token-1', 'redirect_url' => 'https://example.test'], 201),
    ]);
}

test('checkout creates a pending order priced by the server and stores the snap token', function () {
    fakeMidtrans();
    $user = User::factory()->create();

    $order = app(CheckoutServiceInterface::class)->start($user, 'pro_12bln');

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->amount)->toBe(74500)
        ->and($order->snap_token)->toBe('snap-token-1')
        ->and($order->order_id)->toStartWith('SKL-');

    Http::assertSent(fn ($request) => $request['transaction_details']['gross_amount'] === 74500
        && $request['transaction_details']['order_id'] === $order->order_id
        && $request->hasHeader('Authorization'));
});

test('starting checkout twice quickly reuses the same pending order', function () {
    fakeMidtrans();
    $user = User::factory()->create();

    $first = app(CheckoutServiceInterface::class)->start($user, 'pro_12bln');
    $second = app(CheckoutServiceInterface::class)->start($user, 'pro_12bln');

    expect($second->id)->toBe($first->id)
        ->and(Order::query()->count())->toBe(1);
});

test('an unknown plan is refused', function () {
    app(CheckoutServiceInterface::class)->start(User::factory()->create(), 'pro_gratis_selamanya');
})->throws(PaymentException::class);

test('when Midtrans refuses, the order is marked failed and the mentee gets a friendly error', function () {
    Http::fake(['*' => Http::response(['error_messages' => ['x']], 401)]);
    $user = User::factory()->create();

    try {
        app(CheckoutServiceInterface::class)->start($user, 'pro_12bln');
        $this->fail('Expected a PaymentException.');
    } catch (PaymentException $exception) {
        expect($exception->getMessage())->toContain('Pembayaran belum bisa dimulai');
    }

    expect(Order::query()->first()->status)->toBe(OrderStatus::Failed);
});

test('a missing server key stops checkout before any request is sent', function () {
    Http::fake();
    config(['services.midtrans.server_key' => null]);

    expect(fn () => app(CheckoutServiceInterface::class)->start(User::factory()->create(), 'pro_12bln'))
        ->toThrow(PaymentException::class);

    Http::assertNothingSent();
});

test('a visitor who clicks pay is sent to register', function () {
    Livewire::test(Index::class)
        ->call('checkout')
        ->assertRedirect(route('register'));
});

test('a signed-in mentee gets the snap token in the browser and the price comes from the server', function () {
    fakeMidtrans();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('selected', 'pro_1bln')
        ->call('checkout')
        ->assertDispatched('open-snap');

    expect(Order::query()->where('user_id', $user->id)->first()->amount)->toBe(50000);
});

test('an unverified mentee is sent to verify the email first', function () {
    Http::fake();
    Livewire::actingAs(User::factory()->unverified()->create())
        ->test(Index::class)
        ->call('checkout')
        ->assertRedirect(route('verification.notice'));

    Http::assertNothingSent();
});

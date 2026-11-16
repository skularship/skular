<?php

use App\Contracts\CheckoutServiceInterface;
use App\Contracts\ReferralServiceInterface;
use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'pricing.enabled' => true,
        'pricing.launching_until' => now()->addDays(10)->toIso8601String(),
        'services.midtrans.server_key' => 'SB-Mid-server-test',
        'services.midtrans.client_key' => 'SB-Mid-client-test',
    ]);

    Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'snap-token'], 201)]);
});

test('every mentee gets one stable code', function () {
    $user = User::factory()->create();

    $first = app(ReferralServiceInterface::class)->codeFor($user);
    $second = app(ReferralServiceInterface::class)->codeFor($user);

    expect($first->code)->toHaveLength(7)->and($second->id)->toBe($first->id);
});

test('a friend gets 10 percent off the launching price and the order remembers the owner', function () {
    $owner = User::factory()->create();
    $buyer = User::factory()->create();
    $code = app(ReferralServiceInterface::class)->codeFor($owner)->code;

    $order = app(CheckoutServiceInterface::class)->start($buyer, 'pro_12bln', strtolower($code));

    expect($order->amount)->toBe(67050)
        ->and($order->discount_amount)->toBe(7450)
        ->and($order->promo_code)->toBe($code)
        ->and($order->referrer_user_id)->toBe($owner->id);

    Http::assertSent(fn ($request) => ($request['transaction_details']['gross_amount'] ?? null) === 67050);
});

test('the same order is reused only with the same code', function () {
    $owner = User::factory()->create();
    $buyer = User::factory()->create();
    $code = app(ReferralServiceInterface::class)->codeFor($owner)->code;
    $checkout = app(CheckoutServiceInterface::class);

    $plain = $checkout->start($buyer, 'pro_12bln');
    $withCode = $checkout->start($buyer, 'pro_12bln', $code);

    expect($withCode->id)->not->toBe($plain->id)
        ->and($checkout->start($buyer, 'pro_12bln', $code)->id)->toBe($withCode->id);
});

test('refused codes', function (callable $arrange, string $message) {
    [$buyer, $code, $plan] = $arrange();

    expect(fn () => app(CheckoutServiceInterface::class)->start($buyer, $plan, $code))
        ->toThrow(PaymentException::class, $message);
    expect(Order::query()->count())->toBe(0);
})->with([
    'unknown code' => [fn () => [User::factory()->create(), 'SALAH12', 'pro_12bln'], 'tidak ditemukan'],
    'own code' => [function () {
        $user = User::factory()->create();

        return [$user, app(ReferralServiceInterface::class)->codeFor($user)->code, 'pro_12bln'];
    }, 'kodemu sendiri'],
    'monthly plan' => [fn () => [User::factory()->create(), app(ReferralServiceInterface::class)->codeFor(User::factory()->create())->code, 'pro_1bln'], 'paket 12 bulan'],
]);

test('after launching ends the code no longer gives a discount', function () {
    config(['pricing.launching_until' => null]);
    $code = app(ReferralServiceInterface::class)->codeFor(User::factory()->create())->code;

    expect(fn () => app(CheckoutServiceInterface::class)->start(User::factory()->create(), 'pro_12bln', $code))
        ->toThrow(PaymentException::class, 'selama harga launching');
});

test('an account can use a referral code only once', function () {
    $owner = User::factory()->create();
    $buyer = User::factory()->create();
    $code = app(ReferralServiceInterface::class)->codeFor($owner)->code;

    $order = app(CheckoutServiceInterface::class)->start($buyer, 'pro_12bln', $code);
    $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

    expect(fn () => app(CheckoutServiceInterface::class)->start($buyer, 'pro_12bln', $code))
        ->toThrow(PaymentException::class, 'sudah pernah');
});

test('the billing page shows the mentee own code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('payment.index'))->assertOk()
        ->assertSee(app(ReferralServiceInterface::class)->codeFor($user)->code);
});

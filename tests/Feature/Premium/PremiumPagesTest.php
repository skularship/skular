<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;

test('while Premium is off the page, the status page and the landing price section do not exist', function () {
    config(['pricing.enabled' => false]);

    $this->get(route('premium'))->assertNotFound();

    $this->get(route('home'))->assertOk()
        ->assertDontSee('id="harga"', false)
        ->assertDontSee('landing-pricing', false);

    $this->actingAs(User::factory()->profiled()->create())
        ->get(route('dashboard'))->assertOk()
        ->assertDontSee('dashboard-premium', false);
});

test('the Premium page shows the launching prices and the honest Segera labels', function () {
    config([
        'pricing.enabled' => true,
        'pricing.launching_until' => now()->addDays(10)->toIso8601String(),
    ]);

    $this->get(route('premium'))->assertOk()
        ->assertSee('Daftar banyak beasiswa, sekali isi.')
        ->assertSee('Rp6.208')
        ->assertSee('Rp74.500')
        ->assertSee('Hemat 50%')
        ->assertSee('Rp50.000')
        ->assertSee('Segera')
        ->assertSee('Review AI adalah saran, bukan jaminan lolos.');
});

test('after launching the Premium page shows the normal price and a proven strike price', function () {
    config(['pricing.enabled' => true, 'pricing.launching_until' => null]);

    $this->get(route('premium'))->assertOk()
        ->assertSee('Rp12.417')
        ->assertSee('Rp149.000')
        ->assertSee('Hemat 75%')
        ->assertDontSee('Promo berakhir');
});

test('the landing page shows the same prices as the Premium page when Premium is on', function () {
    config(['pricing.enabled' => true, 'pricing.launching_until' => null]);

    $this->get(route('home'))->assertOk()
        ->assertSee('id="harga"', false)
        ->assertSee('href="#harga"', false)
        ->assertSee('Rp12.417')
        ->assertSee('Mulai gratis');
});

test('the dashboard card offers Pro to Gratis mentees and shows the end date to Pro mentees', function () {
    config(['pricing.enabled' => true, 'pricing.launching_until' => null]);
    $user = User::factory()->profiled()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertSee('dashboard-premium', false)
        ->assertSee('Paket Gratis')
        ->assertSee('Lihat paket Pro');

    Subscription::query()->create(['user_id' => $user->id, 'plan' => 'pro_12bln', 'active_until' => now()->addDays(3)]);

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk()
        ->assertSee('Pro aktif')
        ->assertSee('Perpanjang');
});

test('the status page is private to the buyer and reads the order status', function () {
    config(['pricing.enabled' => true]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('payment.status', $order))->assertNotFound();

    $this->actingAs($buyer)->get(route('payment.status', $order))->assertOk()
        ->assertSee('Menunggu pembayaran');

    $order->update(['status' => OrderStatus::Failed]);

    $this->actingAs($buyer)->get(route('payment.status', $order))->assertOk()
        ->assertSee('Pembayaran belum berhasil');
});

test('the billing page lists the buyer transactions only', function () {
    config(['pricing.enabled' => true]);
    $buyer = User::factory()->create();
    $mine = Order::factory()->create(['user_id' => $buyer->id]);
    $other = Order::factory()->create();

    $this->actingAs($buyer)->get(route('payment.index'))->assertOk()
        ->assertSee($mine->order_id)
        ->assertDontSee($other->order_id);
});

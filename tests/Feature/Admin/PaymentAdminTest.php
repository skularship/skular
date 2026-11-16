<?php

use App\Contracts\PricingServiceInterface;
use App\Enums\AdminRole;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\PaymentEvents\Pages\ListPaymentEvents;
use App\Filament\Resources\PaymentEvents\PaymentEventResource;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Subscriptions\Pages\CreateSubscription;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Widgets\PaymentStats;
use App\Jobs\ProcessMidtransNotification;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Create an admin with a seeded role.
 */
function paymentAdmin(AdminRole $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role->value);

    return $user;
}

test('a co-admin can open every payment screen and a staff divisi cannot', function () {
    $co_admin = paymentAdmin(AdminRole::CoAdmin);
    $staff = paymentAdmin(AdminRole::StaffDivisi);

    foreach ([ProductResource::class, OrderResource::class, SubscriptionResource::class, PaymentEventResource::class] as $resource) {
        $this->actingAs($co_admin)->get($resource::getUrl('index'))->assertOk();
        $this->actingAs($staff)->get($resource::getUrl('index'))->assertForbidden();
    }
});

test('a mentee never reaches the payment screens', function () {
    $mentee = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($mentee)->get(OrderResource::getUrl('index'))->assertForbidden();
});

test('an admin changes a price and the public price follows', function () {
    $admin = paymentAdmin(AdminRole::CoAdmin);
    $product = Product::query()->where('code', 'pro_1bln')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['price_normal' => 45000])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->price_normal)->toBe(45000)
        ->and(app(PricingServiceInterface::class)->quote('pro_1bln')->amount)->toBe(45000);
});

test('a launching price must stay below the normal price', function () {
    $admin = paymentAdmin(AdminRole::CoAdmin);
    $product = Product::query()->where('code', 'pro_12bln')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['price_launching' => 200000])
        ->call('save')
        ->assertHasFormErrors(['price_launching']);
});

test('a product cannot be created from the panel and the code is not editable', function () {
    $admin = paymentAdmin(AdminRole::CoAdmin);
    $product = Product::query()->where('code', 'pro_12bln')->firstOrFail();

    expect(ProductResource::canCreate())->toBeFalse();

    Livewire::actingAs($admin)
        ->test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['name' => 'Pro Setahun'])
        ->call('save');

    expect($product->fresh()->code)->toBe('pro_12bln');
});

test('orders are read-only: listed and viewable, never created or deleted', function () {
    $admin = paymentAdmin(AdminRole::CoAdmin);
    $order = Order::factory()->paid()->create();

    expect(OrderResource::canCreate())->toBeFalse();

    Livewire::actingAs($admin)->test(ListOrders::class)->assertCanSeeTableRecords([$order]);
    $this->actingAs($admin)->get(OrderResource::getUrl('view', ['record' => $order]))->assertOk()->assertSee($order->order_id);
    expect($admin->can('Delete', $order))->toBeFalse();
});

test('an admin gives Pro by hand and the mentee gets Pro', function () {
    $admin = paymentAdmin(AdminRole::CoAdmin);
    $mentee = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateSubscription::class)
        ->fillForm(['user_id' => $mentee->id, 'plan' => 'pro_1bln', 'active_until' => now()->addDays(30)])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($mentee->fresh()->hasPro())->toBeTrue();
});

test('an unprocessed notification can be queued again, a processed one cannot', function () {
    Queue::fake();
    $admin = paymentAdmin(AdminRole::CoAdmin);
    $stuck = PaymentEvent::query()->create(['order_id' => 'SKL-X', 'payload' => ['transaction_status' => 'settlement']]);
    $done = PaymentEvent::query()->create(['order_id' => 'SKL-Y', 'payload' => ['transaction_status' => 'settlement'], 'processed_at' => now()]);

    Livewire::actingAs($admin)
        ->test(ListPaymentEvents::class)
        ->assertTableActionVisible('requeue', $stuck)
        ->assertTableActionHidden('requeue', $done)
        ->callAction(TestAction::make('requeue')->table($stuck));

    Queue::assertPushed(ProcessMidtransNotification::class, fn ($job) => $job->paymentEventId === $stuck->id);
});

test('the payment widget shows only while Premium is on and warns about stuck notifications', function () {
    $admin = paymentAdmin(AdminRole::CoAdmin);

    config(['pricing.enabled' => false]);
    $this->actingAs($admin);
    expect(PaymentStats::canView())->toBeFalse();

    config(['pricing.enabled' => true]);
    expect(PaymentStats::canView())->toBeTrue();

    $event = PaymentEvent::query()->create(['order_id' => 'SKL-Z', 'payload' => ['x' => 1]]);
    $event->forceFill(['created_at' => now()->subHour()])->save();

    Livewire::test(PaymentStats::class)->assertSee('Notifikasi tertahan');
});

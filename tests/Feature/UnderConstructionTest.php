<?php

use App\Contracts\PaymentWebhookServiceInterface;
use App\Enums\AdminRole;
use App\Enums\LegalDocument;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/**
 * Turn the under construction switch on and register routes/web.php again,
 * because the condition is read when the routes load (like after
 * `php artisan optimize` in production). Re-registering the same URIs
 * replaces the earlier routes; Fortify, Filament and payments routes stay.
 */
function enableUnderConstruction(): void
{
    config(['app.under_construction' => true]);

    Route::middleware('web')->group(base_path('routes/web.php'));

    $routes = app('router')->getRoutes();
    $routes->refreshNameLookups();
    $routes->refreshActionLookups();
}

test('with the switch off the public and mentee pages work as before', function () {
    expect(config('app.under_construction'))->toBeFalse();

    $this->get(route('home'))->assertOk()->assertDontSee('Skular sedang diperbarui');
    $this->get(route('faq.index'))->assertOk();
    $this->actingAs(User::factory()->profiled()->create())
        ->get(route('dashboard'))->assertOk();
});

test('guests see the under construction page with 503, Retry-After and noindex', function (string $path) {
    enableUnderConstruction();

    $this->get($path)
        ->assertStatus(503)
        ->assertHeader('Retry-After', '3600')
        ->assertSeeText('Skular sedang diperbarui')
        ->assertSee('<meta name="robots" content="noindex" />', false)
        ->assertSee('<meta name="color-scheme" content="light" />', false)
        ->assertSeeText('Muat ulang halaman')
        ->assertSee('https://wa.me/'.config('services.support.whatsapp'), false)
        ->assertSee('data-test="legal-links"', false)
        ->assertSee('data-test="cookie-consent"', false);
})->with(['/', '/faq', '/dashboard']);

test('signed-in mentees see the under construction page on the learning pages', function () {
    $mentee = User::factory()->profiled()->create();
    $module = Module::factory()->create();
    enableUnderConstruction();

    $this->actingAs($mentee);

    foreach ([route('dashboard'), route('roadmap.show'), route('modules.show', $module), route('profile.edit')] as $url) {
        $this->get($url)->assertStatus(503)->assertSeeText('Skular sedang diperbarui');
    }
});

test('health checks keep answering while under construction', function () {
    enableUnderConstruction();

    $this->get('/health')->assertOk();
    $this->get('/up')->assertOk();
});

test('the Midtrans webhook still reaches its controller while under construction', function () {
    $this->mock(PaymentWebhookServiceInterface::class)->shouldReceive('receive')->once();
    enableUnderConstruction();

    $this->postJson(route('payments.midtrans.notification'), ['order_id' => 'SK-1'])
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});

test('the legal pages stay public while under construction', function (LegalDocument $document) {
    enableUnderConstruction();

    $this->get(route($document->routeName()))->assertOk();
})->with(LegalDocument::cases());

test('the admin panel and sign-in keep working while under construction', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(Role::findOrCreate(AdminRole::SuperAdmin->value, 'web'));
    enableUnderConstruction();

    $this->get('/admin/login')->assertOk();
    $this->get(route('login'))->assertOk();
    $this->actingAs($admin)->get('/admin')->assertOk();
});

test('JSON requests get a short JSON 503', function () {
    enableUnderConstruction();

    $this->getJson('/')
        ->assertStatus(503)
        ->assertHeader('Retry-After', '3600')
        ->assertExactJson(['message' => 'Skular sedang diperbarui. Coba lagi sebentar lagi.']);
});

test('every affected route keeps its name while under construction', function () {
    enableUnderConstruction();

    expect(route('home'))->toBe(url('/'))
        ->and(route('dashboard'))->toBe(url('/dashboard'))
        ->and(route('legal.consent'))->toBe(url('/persetujuan'))
        ->and(Route::getRoutes()->getByName('dashboard')->gatherMiddleware())->toContain('under.construction', 'auth')
        ->and(Route::getRoutes()->getByName('health')->gatherMiddleware())->not->toContain('under.construction');
});

test('the under construction page runs no database query', function () {
    enableUnderConstruction();

    DB::enableQueryLog();

    $this->get('/')->assertStatus(503);

    expect(DB::getQueryLog())->toBe([]);
});

test('the middleware lets requests through once the switch is off again', function () {
    enableUnderConstruction();
    config(['app.under_construction' => false]);

    $this->get(route('home'))->assertOk()->assertDontSee('Skular sedang diperbarui');
});

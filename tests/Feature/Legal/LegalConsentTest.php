<?php

use App\Contracts\CheckoutServiceInterface;
use App\Contracts\GoogleAuthServiceInterface;
use App\Enums\ConsentSource;
use App\Enums\LegalDocument;
use App\Livewire\Legal\Consent;
use App\Livewire\Premium\Index as PremiumIndex;
use App\Models\Order;
use App\Models\User;
use App\Models\UserConsent;
use Livewire\Livewire;

/**
 * Consent logging: sign-up requires both statements, the first Google
 * sign-in, older accounts and new document versions go through the consent
 * screen, and checkout logs Terms + Refund.
 */
function registrationInput(array $overrides = []): array
{
    return [
        'name' => 'Mentee Baru',
        'email' => 'mentee@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'age_confirmation' => '1',
        ...$overrides,
    ];
}

test('registration is refused without the terms checkbox', function () {
    $this->post(route('register.store'), registrationInput(['terms' => null]))
        ->assertSessionHasErrors('terms');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('registration is refused without the age statement', function () {
    $this->post(route('register.store'), registrationInput(['age_confirmation' => null]))
        ->assertSessionHasErrors('age_confirmation');

    $this->assertGuest();
});

test('registration logs consent to the current terms and privacy policy', function () {
    $this->post(route('register.store'), registrationInput())->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'mentee@example.com')->firstOrFail();

    expect($user->consents()->count())->toBe(2);

    foreach ([LegalDocument::Terms, LegalDocument::Privacy] as $document) {
        $consent = $user->consents()->where('document', $document)->firstOrFail();

        expect($consent->version)->toBe($document->version())
            ->and($consent->source)->toBe(ConsentSource::Register)
            ->and($consent->age_confirmed)->toBeTrue();
    }
});

test('the register form shows both statements with links to the documents', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('data-test="register-terms"', false)
        ->assertSee('data-test="register-age"', false)
        ->assertSee('href="'.route('legal.terms').'" target="_blank"', false)
        ->assertSee('href="'.route('legal.privacy').'" target="_blank"', false);
});

test('a new google account must accept before reaching the dashboard', function () {
    $user = app(GoogleAuthServiceInterface::class)->resolveUser([
        'id' => 'google-123',
        'email' => 'google@example.com',
        'email_verified' => true,
        'name' => 'Mentee Google',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('legal.consent'));

    Livewire::actingAs($user)
        ->test(Consent::class)
        ->set('terms', true)
        ->set('age_confirmation', true)
        ->call('accept')
        ->assertRedirect(route('dashboard'));

    expect($user->consents()->pluck('source')->all())
        ->each->toBe(ConsentSource::Google);

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
});

test('an existing account without consent is asked once and keeps its data', function () {
    $user = User::factory()->profiled()->withoutConsent()->create();
    $gpa = $user->gpa;

    $this->actingAs($user)
        ->get(route('roadmap.show'))
        ->assertRedirect(route('legal.consent'));

    Livewire::actingAs($user)
        ->test(Consent::class)
        ->set('terms', true)
        ->set('age_confirmation', true)
        ->call('accept')
        ->assertRedirect(route('roadmap.show'));

    expect($user->consents()->pluck('source')->unique()->all())->toBe([ConsentSource::Reconsent])
        ->and($user->fresh()->gpa)->toBe($gpa);
});

test('the consent screen refuses to continue without both statements', function () {
    $user = User::factory()->withoutConsent()->create();

    Livewire::actingAs($user)
        ->test(Consent::class)
        ->set('terms', true)
        ->call('accept')
        ->assertHasErrors(['age_confirmation' => 'accepted']);

    expect($user->consents()->count())->toBe(0);
});

test('a new terms version asks every mentee to accept again', function () {
    $user = User::factory()->profiled()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    config(['legal.documents.terms.version' => '2.0']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('legal.consent'));

    $this->get(route('legal.consent'))
        ->assertOk()
        ->assertSee('Syarat & Ketentuan')
        ->assertSee('(versi 2.0)');
});

test('legal pages, logout and the health check stay reachable without consent', function () {
    $user = User::factory()->withoutConsent()->create();

    $this->actingAs($user);

    foreach (LegalDocument::cases() as $document) {
        $this->get(route($document->routeName()))->assertOk();
    }

    $this->get(route('health'))->assertSuccessful();

    $this->post(route('logout'))->assertRedirect();
    $this->assertGuest();
});

test('a mentee with current consent skips the consent screen', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Consent::class)
        ->assertRedirect(route('dashboard'));
});

test('checkout logs consent to the terms and refund policy once per version', function () {
    config(['pricing.enabled' => true]);

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $checkout = Mockery::mock(CheckoutServiceInterface::class);
    $checkout->shouldReceive('start')->twice()->andReturn($order);
    app()->instance(CheckoutServiceInterface::class, $checkout);

    $component = Livewire::actingAs($user)->test(PremiumIndex::class);
    $component->call('checkout')->assertDispatched('open-snap');
    $component->call('checkout');

    $logged = UserConsent::query()
        ->where('user_id', $user->id)
        ->where('source', ConsentSource::Checkout)
        ->pluck('document')
        ->all();

    expect($logged)->toEqualCanonicalizing([LegalDocument::Terms, LegalDocument::Refund]);
});

test('the premium page shows the consent note next to the pay button', function () {
    config(['pricing.enabled' => true]);

    $this->get(route('premium'))
        ->assertOk()
        ->assertSee('data-test="premium-consent"', false)
        ->assertSee('href="'.route('legal.refund').'"', false);
});

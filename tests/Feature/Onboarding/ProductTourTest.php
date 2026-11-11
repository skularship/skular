<?php

use App\Contracts\ProductTourServiceInterface;
use App\Enums\ProductTour;
use App\Livewire\Onboarding\Guide;
use App\Livewire\Onboarding\ProductTour as ProductTourComponent;
use App\Models\User;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create();
});

test('every tour has steps with a title and a body', function (ProductTour $tour) {
    $steps = app(ProductTourServiceInterface::class)->steps($tour);

    expect($steps)->not->toBeEmpty();

    foreach ($steps as $step) {
        expect(trim($step->title))->not->toBe('')
            ->and(trim($step->body))->not->toBe('');
    }
})->with(ProductTour::cases());

test('the module tour quotes the configured AI Mentor quota and never promises a numeric score', function () {
    config(['ai.module_chat.limit' => 7]);

    $copy = collect(app(ProductTourServiceInterface::class)->steps(ProductTour::Module))
        ->map(fn ($step): string => $step->body)
        ->implode(' ');

    expect($copy)->toContain('maksimal 7 pertanyaan per modul')
        ->toContain('label')
        ->not->toContain('skor');
});

test('a tour opens by itself until the mentee finishes it', function () {
    $component = Livewire::actingAs($this->mentee)
        ->test(ProductTourComponent::class, ['tour' => ProductTour::Dashboard->value])
        ->assertViewHas('auto_start', true)
        ->assertSeeHtml('data-tour-overlay="dashboard"');

    $component->call('complete');

    $this->mentee->refresh();

    expect($this->mentee->onboarding_tours)->toHaveKey('dashboard')
        ->and(app(ProductTourServiceInterface::class)->shouldAutoStart($this->mentee, ProductTour::Dashboard))->toBeFalse()
        // Other tours are unaffected.
        ->and(app(ProductTourServiceInterface::class)->shouldAutoStart($this->mentee, ProductTour::Roadmap))->toBeTrue();

    Livewire::actingAs($this->mentee)
        ->test(ProductTourComponent::class, ['tour' => ProductTour::Dashboard->value])
        ->assertViewHas('auto_start', false);
});

test('the tour key cannot be swapped from the browser', function () {
    Livewire::actingAs($this->mentee)
        ->test(ProductTourComponent::class, ['tour' => ProductTour::Dashboard->value])
        ->set('tour', ProductTour::Roadmap->value);
})->throws(CannotUpdateLockedPropertyException::class);

test('each journey page embeds its tour', function (string $route, string $tour) {
    $this->actingAs($this->mentee)
        ->get(route($route))
        ->assertOk()
        ->assertSee('data-tour-overlay="'.$tour.'"', false);
})->with([
    'profiling' => ['profiling', 'profiling'],
    'recommendation' => ['profiling.recommendation', 'recommendation'],
    'dashboard' => ['dashboard', 'dashboard'],
    'roadmap' => ['roadmap.show', 'roadmap'],
    'tracker' => ['scholarships.tracker', 'tracker'],
]);

test('the sidebar navigation is a tour target and links to the guide', function () {
    $this->actingAs($this->mentee)
        ->get(route('dashboard'))
        ->assertSee('data-tour="main-nav"', false)
        ->assertSee(route('guide.index'));
});

test('the landing page carries the visitor tour remembered in the browser', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-tour-overlay="landing"', false)
        ->assertSee('Selamat datang di Skular');
});

test('guests are redirected from the guide to the login page', function () {
    $this->get(route('guide.index'))->assertRedirect(route('login'));
});

test('the guide lists every stage of the journey in order', function () {
    $this->actingAs($this->mentee)
        ->get(route('guide.index'))
        ->assertOk()
        ->assertSeeInOrder(array_map(fn (ProductTour $tour): string => $tour->label(), ProductTour::cases()))
        ->assertSee('Hal penting yang perlu kamu tahu');
});

test('replaying a tour forgets it and opens its page', function () {
    $service = app(ProductTourServiceInterface::class);
    $service->markCompleted($this->mentee, ProductTour::Roadmap);

    Livewire::actingAs($this->mentee)
        ->test(Guide::class)
        ->assertSee('Tur sudah dilihat')
        ->call('replay', ProductTour::Roadmap->value)
        ->assertRedirect(route('roadmap.show', ['tour' => 'roadmap']));

    expect($service->isCompleted($this->mentee->refresh(), ProductTour::Roadmap))->toBeFalse();
});

test('replaying the module tour waits for the next module', function () {
    app(ProductTourServiceInterface::class)->markCompleted($this->mentee, ProductTour::Module);

    Livewire::actingAs($this->mentee)
        ->test(Guide::class)
        ->call('replay', ProductTour::Module->value)
        ->assertRedirect(route('roadmap.show'))
        ->assertDispatched('toast');

    expect($this->mentee->refresh()->onboarding_tours)->toBeNull();
});

test('an unknown tour key is ignored by the guide', function () {
    Livewire::actingAs($this->mentee)
        ->test(Guide::class)
        ->call('replay', 'admin')
        ->assertNoRedirect();
});

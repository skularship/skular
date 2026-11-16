<?php

use App\Contracts\PricingServiceInterface;

test('without a launching end date every plan sells at its normal price', function () {
    config(['pricing.launching_until' => null]);

    $quotes = app(PricingServiceInterface::class)->quotes();
    $year = $quotes->get('pro_12bln');
    $month = $quotes->get('pro_1bln');

    expect($year->amount)->toBe(149000)
        ->and($year->is_launching)->toBeFalse()
        ->and($year->monthly)->toBe(12417)
        ->and($year->strike_total)->toBe(600000)
        ->and($year->strike_monthly)->toBe(50000)
        ->and($year->saving_percent)->toBe(75)
        ->and($month->amount)->toBe(50000)
        ->and($month->strike_total)->toBeNull()
        ->and($month->saving_percent)->toBeNull();
});

test('while the launching promo runs the 12 month plan costs Rp74.500 against the normal price', function () {
    config(['pricing.launching_until' => now()->addDays(10)->toIso8601String()]);

    $year = app(PricingServiceInterface::class)->quote('pro_12bln');
    $month = app(PricingServiceInterface::class)->quote('pro_1bln');

    expect($year->amount)->toBe(74500)
        ->and($year->is_launching)->toBeTrue()
        ->and($year->monthly)->toBe(6208)
        ->and($year->strike_total)->toBe(149000)
        ->and($year->strike_monthly)->toBe(12417)
        ->and($year->saving_percent)->toBe(50)
        ->and($year->launching_until)->not->toBeNull()
        ->and($month->amount)->toBe(50000)
        ->and($month->is_launching)->toBeFalse();
});

test('a launching date that has passed or is not a date switches the promo off', function (?string $value) {
    config(['pricing.launching_until' => $value]);

    expect(app(PricingServiceInterface::class)->quote('pro_12bln')->amount)->toBe(149000);
})->with([
    'in the past' => fn () => now()->subDay()->toIso8601String(),
    'not a date' => 'segera',
    'empty' => '',
]);

test('an inactive or unknown plan has no quote', function () {
    expect(app(PricingServiceInterface::class)->quote('pro_5tahun'))->toBeNull();
});

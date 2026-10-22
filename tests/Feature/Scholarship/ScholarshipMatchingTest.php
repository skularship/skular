<?php

use App\Contracts\ScholarshipMatchingServiceInterface;
use App\Data\UserProfileData;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Enums\MatchTier;
use App\Models\Scholarship;
use App\Models\User;

beforeEach(function () {
    $this->matching = app(ScholarshipMatchingServiceInterface::class);
});

test('scholarships of an unrelated education level are not matched', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S2)->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    $matches = $this->matching->match(UserProfileData::fromUser($mentee));

    expect($matches)->toHaveCount(0);
});

test('a sma mentee is also matched with smk scholarships', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::SMA,
        'age' => 17,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::SMK)->create([
        'scope' => LocationScope::DalamNegeri,
        'maximum_age' => 20,
        'minimum_value' => null,
    ]);

    $matches = $this->matching->match(UserProfileData::fromUser($mentee));

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->scholarship->level)->toBe(EducationLevel::SMK);
});

test('a mentee is only matched with scholarships in their desired location', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::LuarNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    $matches = $this->matching->match(UserProfileData::fromUser($mentee));

    expect($matches)->toHaveCount(0);
});

test('a scholarship without a location scope is available to every mentee', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::LuarNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->create([
        'scope' => null,
        'minimum_value' => null,
        'maximum_age' => null,
    ]);

    $matches = $this->matching->match(UserProfileData::fromUser($mentee));

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->tier)->toBe(MatchTier::VeryGood);
});

test('a gpa below the minimum requirement is reported as unmet', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'gpa' => 3.00,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->create([
        'scope' => null,
        'minimum_value' => 3.50,
        'maximum_age' => null,
    ]);

    $match = $this->matching->match(UserProfileData::fromUser($mentee))->first();

    $gpa_reason = collect($match->reasons)->firstWhere('label', 'IPK');

    expect($match->tier)->toBe(MatchTier::Low)
        ->and($gpa_reason->satisfied)->toBeFalse()
        ->and($gpa_reason->detail)->toContain('belum memenuhi')
        ->and($match->warnings())->toHaveCount(1);
});

test('an age above the maximum requirement is reported as unmet', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'age' => 30,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->create([
        'scope' => null,
        'minimum_value' => null,
        'maximum_age' => 25,
    ]);

    $match = $this->matching->match(UserProfileData::fromUser($mentee))->first();

    expect($match->tier)->toBe(MatchTier::Low)
        ->and($match->warnings())->toHaveCount(1)
        ->and($match->warnings()[0]->detail)->toContain('melebihi batas maksimal');
});

test('a scholarship requiring sktm is unmet when the mentee has none', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'has_sktm' => false,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->requiringSktm()->create([
        'scope' => null,
        'minimum_value' => null,
        'maximum_age' => null,
    ]);

    $match = $this->matching->match(UserProfileData::fromUser($mentee))->first();

    expect($match->tier)->toBe(MatchTier::Low)
        ->and($match->warnings())->toHaveCount(1)
        ->and($match->warnings()[0]->detail)->toContain('SKTM');
});

test('an unrestricted scholarship explains that no requirements apply', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => null,
    ]);

    $match = $this->matching->match(UserProfileData::fromUser($mentee))->first();

    $labels = collect($match->reasons)->pluck('detail');

    expect($match->tier)->toBe(MatchTier::VeryGood)
        ->and($labels)->toContain('Tidak ada batasan IPK minimum.')
        ->and($labels)->toContain('Tidak ada batasan usia maksimal.')
        ->and($labels)->toContain('Tidak memerlukan SKTM/KHI.');
});

test('drafts and archived scholarships are never recommended', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->draft()->create(['scope' => null]);

    expect($this->matching->match(UserProfileData::fromUser($mentee)))->toHaveCount(0);
});

test('an urgent deadline downgrades a perfect match', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->closingSoon(3)->create([
        'scope' => null,
    ]);

    $match = $this->matching->match(UserProfileData::fromUser($mentee))->first();

    expect($match->tier)->toBe(MatchTier::Good)
        ->and($match->score)->toBeLessThan(100);
});

test('matches are ordered with the strongest tier first', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'gpa' => 3.00,
        'age' => 21,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->create([
        'scholarship_name' => 'Beasiswa Kurang Cocok',
        'scope' => null,
        'minimum_value' => 3.75,
        'maximum_age' => null,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scholarship_name' => 'Beasiswa Sangat Cocok',
        'scope' => null,
    ]);

    $matches = $this->matching->match(UserProfileData::fromUser($mentee));

    expect($matches->first()->scholarship->scholarship_name)->toBe('Beasiswa Sangat Cocok')
        ->and($matches->last()->tier)->toBe(MatchTier::Low);
});

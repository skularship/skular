<?php

use App\Contracts\ActivityTrackerInterface;
use App\Enums\ActivityName;
use App\Enums\AdminRole;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Filament\Widgets\MenteeFunnel;
use App\Livewire\Profiling\Wizard;
use App\Livewire\Scholarships\Index;
use App\Livewire\Scholarships\Recommendation;
use App\Livewire\Scholarships\Show;
use App\Models\ActivityLog;
use App\Models\Cluster;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Count the stored rows of one tracked action.
 */
function trackedCount(ActivityName $name): int
{
    return ActivityLog::query()->where('name', $name)->count();
}

test('a failing insert never throws and is logged instead', function () {
    Log::spy();
    Schema::drop('activity_logs');

    app(ActivityTrackerInterface::class)->track(ActivityName::ScholarshipsViewed, ['x' => 1], User::factory()->create());

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message): bool => $message === 'Activity tracking failed');
});

test('registering records exactly one registered event', function () {
    $this->post(route('register.store'), [
        'name' => 'Mentee Baru',
        'email' => 'baru@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'age_confirmation' => '1',
    ])->assertRedirect();

    $log = ActivityLog::query()->sole();

    expect($log->name)->toBe(ActivityName::Registered)
        ->and($log->user_id)->toBe(User::query()->where('email', 'baru@example.com')->value('id'));
});

test('the catalog records the visit, each filter change and the match button once', function () {
    $mentee = User::factory()->create();

    Livewire::actingAs($mentee)
        ->test(Index::class)
        ->set('level', EducationLevel::S1->value)
        ->set('search', 'rahasia pribadi')
        ->call('findMatches')
        ->assertRedirect(route('profiling'));

    $filters = ActivityLog::query()->where('name', ActivityName::ScholarshipsFiltered)->orderBy('id')->pluck('properties')->all();

    expect(trackedCount(ActivityName::ScholarshipsViewed))->toBe(1)
        ->and(trackedCount(ActivityName::MatchCtaClicked))->toBe(1)
        ->and($filters)->toBe([
            ['filter' => 'level', 'value' => EducationLevel::S1->value],
            ['filter' => 'search', 'value' => true],
        ]);
});

test('opening a scholarship and adding it records both actions once', function () {
    $mentee = User::factory()->profiled()->create();
    $scholarship = Scholarship::factory()->create();

    Livewire::actingAs($mentee)
        ->test(Show::class, ['scholarship' => $scholarship])
        ->call('addToSelection');

    expect(trackedCount(ActivityName::ScholarshipOpened))->toBe(1)
        ->and(trackedCount(ActivityName::ScholarshipsSelected))->toBe(1)
        ->and(ActivityLog::query()->where('name', ActivityName::ScholarshipOpened)->value('properties'))
        ->toBe(['scholarship_id' => $scholarship->id]);
});

test('profiling records start and completion once', function () {
    $cluster = Cluster::factory()->create();
    $mentee = User::factory()->create();

    Livewire::actingAs($mentee)
        ->test(Wizard::class)
        ->call('submit', [
            'level' => 'S1',
            'age' => '21',
            'gpa' => '3.75',
            'has_sktm' => '1',
            'desired_level' => 'S2',
            'desired_location' => 'luar_negeri',
            'cluster' => (string) $cluster->id,
            'phone' => '081234567890',
        ]);

    expect(trackedCount(ActivityName::ProfilingStarted))->toBe(1)
        ->and(trackedCount(ActivityName::ProfilingCompleted))->toBe(1);
});

test('saving the recommendation selection records it once', function () {
    $mentee = User::factory()->profiled()->create(['level' => EducationLevel::S1, 'desired_location' => LocationScope::DalamNegeri]);
    $scholarship = Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create(['scope' => LocationScope::DalamNegeri]);

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->set('selected', [$scholarship->id])
        ->call('confirmSelection');

    expect(trackedCount(ActivityName::ScholarshipsSelected))->toBe(1);
});

test('the funnel counts distinct mentees of the registration window per step', function () {
    $tracker = app(ActivityTrackerInterface::class);
    [$first, $second, $old] = User::factory()->count(3)->create()->all();

    foreach ([$first, $second] as $mentee) {
        $tracker->track(ActivityName::Registered, user: $mentee);
        $tracker->track(ActivityName::ScholarshipsViewed, user: $mentee);
    }

    $tracker->track(ActivityName::ScholarshipsViewed, user: $first);
    $tracker->track(ActivityName::MatchCtaClicked, user: $first);

    $this->travel(-20)->days();
    $tracker->track(ActivityName::Registered, user: $old);
    $this->travelBack();
    $tracker->track(ActivityName::MatchCtaClicked, user: $old);

    $week = collect($tracker->funnel(7))->keyBy(fn ($step) => $step->step->value);
    $month = collect($tracker->funnel(30))->keyBy(fn ($step) => $step->step->value);

    expect($week['registered']->count)->toBe(2)
        ->and($week['scholarships_viewed']->count)->toBe(2)
        ->and($week['scholarships_viewed']->continued_percentage)->toBe(100)
        ->and($week['match_cta_clicked']->count)->toBe(1)
        ->and($week['match_cta_clicked']->continued_percentage)->toBe(50)
        ->and($week['profiling_completed']->count)->toBe(0)
        ->and($week['scholarships_selected']->continued_percentage)->toBeNull()
        ->and($month['registered']->count)->toBe(3)
        ->and($month['match_cta_clicked']->count)->toBe(2);
});

test('the funnel widget renders for admins who may list mentees', function () {
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    $role->syncPermissions([Permission::findOrCreate('ViewAny:User', 'web')]);
    $admin = User::factory()->create();
    $admin->assignRole($role);

    app(ActivityTrackerInterface::class)->track(ActivityName::Registered, user: User::factory()->create());

    Livewire::actingAs($admin)
        ->test(MenteeFunnel::class)
        ->assertSee('Funnel Mentee')
        ->assertSee('Klik cari yang sesuai')
        ->set('days', 30)
        ->assertSeeHtml('data-test="mentee-funnel"');
});

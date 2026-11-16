<?php

use App\Jobs\SendDeadlineReminder;
use App\Mail\DeadlineReminderMail;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['pricing.enabled' => true]);
});

/**
 * Create an application whose scholarship closes in the given number of days.
 */
function applicationClosingIn(User $user, int $days, bool $primary = false): ScholarshipApplication
{
    return ScholarshipApplication::factory()->create([
        'user_id' => $user->id,
        'is_primary' => $primary,
        'scholarship_id' => Scholarship::factory()->create(['closing_date' => now()->addDays($days)->toDateString()])->id,
    ]);
}

test('the daily command queues one job per application at 7, 3 and 1 day and ignores other days', function () {
    Queue::fake();
    $user = User::factory()->create();
    applicationClosingIn($user, 7);
    applicationClosingIn($user, 3);
    applicationClosingIn($user, 1);
    applicationClosingIn($user, 5);

    $this->artisan('reminders:dispatch')->assertSuccessful();

    Queue::assertPushedOn('reminders', SendDeadlineReminder::class, 3);
});

test('the command does nothing while Premium is off', function () {
    config(['pricing.enabled' => false]);
    Queue::fake();
    applicationClosingIn(User::factory()->create(), 3);

    $this->artisan('reminders:dispatch')->assertSuccessful();

    Queue::assertNothingPushed();
});

test('a reminder email is sent once and never twice for the same offset', function () {
    Mail::fake();
    $application = applicationClosingIn(User::factory()->create(), 3, primary: true);

    SendDeadlineReminder::dispatchSync($application->id, 3);
    SendDeadlineReminder::dispatchSync($application->id, 3);

    Mail::assertQueued(DeadlineReminderMail::class, 1);
});

test('a Gratis mentee gets reminders for the primary scholarship only, Pro for all', function () {
    Mail::fake();
    $free = User::factory()->create();
    $primary = applicationClosingIn($free, 3, primary: true);
    $second = applicationClosingIn($free, 3);

    SendDeadlineReminder::dispatchSync($primary->id, 3);
    SendDeadlineReminder::dispatchSync($second->id, 3);
    Mail::assertQueued(DeadlineReminderMail::class, 1);

    $pro = User::factory()->create();
    Subscription::query()->create(['user_id' => $pro->id, 'plan' => 'pro_1bln', 'active_until' => now()->addDays(20)]);
    $a = applicationClosingIn($pro, 3, primary: true);
    $b = applicationClosingIn($pro, 3);

    SendDeadlineReminder::dispatchSync($a->id, 3);
    SendDeadlineReminder::dispatchSync($b->id, 3);
    Mail::assertQueued(DeadlineReminderMail::class, 3);
});

test('no reminder is sent for an application already submitted', function () {
    Mail::fake();
    $application = applicationClosingIn(User::factory()->create(), 3, primary: true);
    $application->update(['status' => App\Enums\ApplicationStatus::Submitted]);

    SendDeadlineReminder::dispatchSync($application->id, 3);

    Mail::assertNothingQueued();
});

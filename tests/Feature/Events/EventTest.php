<?php

use App\Contracts\EventServiceInterface;
use App\Enums\ActivityName;
use App\Enums\AdminRole;
use App\Enums\EventAudience;
use App\Enums\EventRegistrationStatus;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Livewire\Dashboard;
use App\Livewire\Events\Index;
use App\Livewire\Events\Show;
use App\Mail\EventReminderMail;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create();
    $this->events = app(EventServiceInterface::class);
});

test('the event list shows upcoming events with their WIB time and hides drafts', function () {
    Event::factory()->create(['title' => 'Webinar Esai LPDP', 'starts_at' => '2026-11-25 12:00:00', 'ends_at' => '2026-11-25 14:00:00']);
    Event::factory()->draft()->create(['title' => 'Webinar Rahasia']);

    $this->actingAs($this->mentee)->get(route('events.index'))->assertOk();

    Livewire::actingAs($this->mentee)
        ->test(Index::class)
        ->assertSee('Webinar Esai LPDP')
        ->assertSee('19.00–21.00 WIB')
        ->assertDontSee('Webinar Rahasia');
});

test('registering twice keeps one seat and records event_registered once', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['event' => $event])
        ->call('startRegistration')
        ->assertHasNoErrors()
        ->assertSee('Kamu terdaftar.')
        ->assertSee('Link acara muncul di sini H-1.')
        ->call('register');

    $this->events->register($this->mentee, $event);

    expect(EventRegistration::query()->where('event_id', $event->id)->count())->toBe(1)
        ->and(ActivityLog::query()->where('name', ActivityName::EventRegistered)->count())->toBe(1);
});

test('registration questions must be answered before the seat is taken', function () {
    $event = Event::factory()->create(['registration_questions' => ['Apa target beasiswamu?']]);

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['event' => $event])
        ->call('startRegistration')
        ->assertSet('answering', true)
        ->assertSee('Apa target beasiswamu?')
        ->call('register')
        ->assertHasErrors('answers.0')
        ->set('answers.0', 'LPDP S2 ke Belanda')
        ->call('register')
        ->assertHasNoErrors();

    expect($this->events->registrationFor($this->mentee, $event)->answers)
        ->toBe([['question' => 'Apa target beasiswamu?', 'answer' => 'LPDP S2 ke Belanda']]);
});

test('a full event refuses a new registration but keeps existing seats', function () {
    $event = Event::factory()->create(['capacity' => 1]);
    $first = User::factory()->create();

    $this->events->register($first, $event);

    expect(fn () => $this->events->register($this->mentee, $event))
        ->toThrow(ValidationException::class, 'Maaf, kursi acara ini sudah penuh.');

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['event' => $event])
        ->assertSeeHtml('data-test="event-full"')
        ->assertSee('Penuh');

    expect($this->events->register($first, $event)->status)->toBe(EventRegistrationStatus::Registered)
        ->and(EventRegistration::query()->count())->toBe(1);
});

test('a cancelled seat frees the capacity for someone else', function () {
    $event = Event::factory()->create(['capacity' => 1]);

    $this->events->register($this->mentee, $event);
    $this->events->cancel($this->mentee, $event);

    expect($this->events->register(User::factory()->create(), $event)->status)->toBe(EventRegistrationStatus::Registered);
});

test('a Pro-only event is enforced on the server', function () {
    $event = Event::factory()->create(['audience' => EventAudience::Pro]);

    $this->actingAs($this->mentee)->get(route('events.show', $event))->assertNotFound();

    expect(fn () => $this->events->register($this->mentee, $event))->toThrow(ValidationException::class)
        ->and($this->events->upcoming($this->mentee))->toBeEmpty();

    Subscription::query()->create(['user_id' => $this->mentee->id, 'plan' => 'pro_1bln', 'active_until' => now()->addDays(20)]);
    $this->mentee->unsetRelation('proSubscription');

    expect($this->events->register($this->mentee, $event)->status)->toBe(EventRegistrationStatus::Registered);
});

test('an invitation event is only for mentees with the invite tag', function () {
    $event = Event::factory()->create(['audience' => EventAudience::Undangan, 'invite_tag' => 'alumni_akademi_unggulan']);
    $alumnus = User::factory()->create(['tags' => ['alumni_akademi_unggulan']]);

    expect($this->events->canSee($this->mentee, $event))->toBeFalse()
        ->and(fn () => $this->events->register($this->mentee, $event))->toThrow(ValidationException::class)
        ->and($this->events->canSee($alumnus, $event))->toBeTrue()
        ->and($this->events->register($alumnus, $event)->status)->toBe(EventRegistrationStatus::Registered);
});

test('the meeting link is shown only to registered mentees from H-1', function () {
    $event = Event::factory()->startingIn(3 * 24 * 60)->create(['location_url' => 'https://meet.example.com/rahasia']);
    $this->events->register($this->mentee, $event);
    $stranger = User::factory()->create();

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['event' => $event])
        ->assertDontSee('https://meet.example.com/rahasia')
        ->assertSee('Link acara muncul di sini H-1.');

    $this->travelTo($event->starts_at->subHours(23));

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['event' => $event])
        ->assertSeeHtml('data-test="event-link"')
        ->assertSee('https://meet.example.com/rahasia');

    Livewire::actingAs($stranger)
        ->test(Show::class, ['event' => $event])
        ->assertDontSee('https://meet.example.com/rahasia');
});

test('the calendar file holds the event page and never the meeting link', function () {
    $event = Event::factory()->create(['location_url' => 'https://meet.example.com/rahasia']);

    $response = $this->actingAs($this->mentee)->get(route('events.calendar', $event))->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/calendar')
        ->and($response->getContent())->toContain('BEGIN:VEVENT')
        ->and($response->getContent())->toContain('SUMMARY:'.$event->title)
        ->and($response->getContent())->not->toContain('meet.example.com');
});

test('reminders are queued once per kind and never twice', function () {
    Mail::fake();

    $tomorrow = Event::factory()->startingIn(20 * 60)->create();
    $soon = Event::factory()->startingIn(45)->create();
    $later = Event::factory()->startingIn(3 * 24 * 60)->create();

    foreach ([$tomorrow, $soon, $later] as $event) {
        $this->events->register($this->mentee, $event);
    }

    $this->artisan('events:remind')->assertSuccessful();
    $this->artisan('events:remind')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, 2);
    Mail::assertQueued(EventReminderMail::class, fn (EventReminderMail $mail): bool => $mail->registration->event_id === $tomorrow->id && $mail->kind === 'day');
    Mail::assertQueued(EventReminderMail::class, fn (EventReminderMail $mail): bool => $mail->registration->event_id === $soon->id && $mail->kind === 'hour');

    $this->travelTo($tomorrow->starts_at->subMinutes(30));
    $this->artisan('events:remind')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, 3);
    Mail::assertQueued(EventReminderMail::class, fn (EventReminderMail $mail): bool => $mail->registration->event_id === $tomorrow->id && $mail->kind === 'hour');
});

test('after the event the mentee rates it 1-5 with one sentence', function () {
    $event = Event::factory()->create();
    $this->events->register($this->mentee, $event);
    $this->travelTo($event->ends_at->addHour());

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['event' => $event])
        ->assertSeeHtml('data-test="event-feedback"')
        ->set('feedback_score', 4)
        ->set('feedback_text', 'Contoh esainya sangat membantu.')
        ->call('sendFeedback')
        ->assertHasNoErrors();

    expect($this->events->registrationFor($this->mentee, $event)->feedback_score)->toBe(4);
});

test('the dashboard shows the next event for the mentee', function () {
    Event::factory()->create(['title' => 'Kelas Intensif Wawancara']);

    Livewire::actingAs($this->mentee)
        ->test(Dashboard::class)
        ->assertSee('Acara berikutnya untukmu')
        ->assertSee('Kelas Intensif Wawancara');
});

test('an event with registrations cannot be deleted', function () {
    $event = Event::factory()->create();
    $this->events->register($this->mentee, $event);

    expect(fn () => $event->delete())->toThrow(LogicException::class);
});

test('admins mark participants as attended in bulk and it is tracked once', function () {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(AdminRole::CoAdmin->value);

    $event = Event::factory()->past()->create();
    $registration = EventRegistration::factory()->create(['event_id' => $event->id, 'user_id' => $this->mentee->id]);

    $this->actingAs($admin)->get(route('filament.admin.resources.events.index'))->assertOk();

    Livewire::actingAs($admin)
        ->test(RegistrationsRelationManager::class, ['ownerRecord' => $event, 'pageClass' => EditEvent::class])
        ->assertCanSeeTableRecords([$registration])
        ->selectTableRecords([$registration->id])
        ->callAction(TestAction::make('markAttended')->table()->bulk())
        ->assertHasNoActionErrors();

    expect($registration->refresh()->status)->toBe(EventRegistrationStatus::Attended)
        ->and(ActivityLog::query()->where('name', ActivityName::EventAttended)->count())->toBe(1);
});

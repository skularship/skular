<?php

use App\Ai\Agents\IqMentorAgent;
use App\Contracts\IqAttemptServiceInterface;
use App\Contracts\IqMentorQuotaServiceInterface;
use App\Contracts\IqMentorServiceInterface;
use App\Exceptions\IqLockedException;
use App\Livewire\Iq\MentorChat;
use App\Models\IqAttempt;
use App\Models\IqTest;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Livewire\Livewire;

beforeEach(function () {
    config(['pricing.enabled' => true, 'iq.mentor.daily_limit' => 2]);
    $this->user = User::factory()->create();
    $test = IqTest::factory()->withQuestions(['numerik' => 2, 'logika' => 2])->create(['mentor_notes' => 'CATATAN-MATERI: cari selisih antarangka.']);
    $attempts = app(IqAttemptServiceInterface::class);
    $this->attempt = $attempts->start($this->user, $test);
    $attempts->answer($this->attempt, 1, 'B');
    $attempts->finish($this->attempt);
    $this->attempt->refresh();
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Give the mentee paid access.
 */
function iqMentorPaid(User $user): User
{
    Subscription::query()->create(['user_id' => $user->id, 'plan' => 'pro_1bln', 'active_until' => now()->addMonth()]);

    return $user->fresh();
}

test('a free account cannot open the mentor chat or ask the mentor', function () {
    IqMentorAgent::fake()->preventStrayPrompts();

    Livewire::actingAs($this->user)->test(MentorChat::class, ['attempt_id' => $this->attempt->id])->assertForbidden();

    expect(fn () => app(IqMentorServiceInterface::class)->ask($this->user, $this->attempt, 'Kenapa?'))
        ->toThrow(IqLockedException::class);

    IqMentorAgent::assertNeverPrompted();
});

test('the mentor gets the key, the explanation, the mentee choice and the material notes as context', function () {
    $user = iqMentorPaid($this->user);
    IqMentorAgent::fake(['Polanya bertambah 2.']);

    $reply = app(IqMentorServiceInterface::class)->ask($user, $this->attempt, 'Kenapa jawabanku salah?', 1);

    expect($reply->answer)->toBe('Polanya bertambah 2.');

    IqMentorAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Kunci jawaban: A')
        && str_contains($prompt->prompt, 'Jawaban mentee: B (salah)')
        && str_contains($prompt->prompt, 'Polanya selalu bertambah 2.')
        && str_contains($prompt->prompt, 'CATATAN-MATERI')
        && str_contains($prompt->prompt, '<pertanyaan_mentee>'));
});

test('the mentor refuses a position outside the attempt', function () {
    $user = iqMentorPaid($this->user);
    IqMentorAgent::fake()->preventStrayPrompts();

    expect(fn () => app(IqMentorServiceInterface::class)->ask($user, $this->attempt, 'Kenapa?', 99))
        ->toThrow(ValidationException::class);
});

test('the instructions forbid IQ numbers and scholarship promises', function () {
    $instructions = (new IqMentorAgent('konteks'))->instructions();

    expect($instructions)->toContain('Jangan pernah menyebut, memperkirakan, atau menghitung angka IQ')
        ->toContain('Jangan menjanjikan atau memperkirakan peluang lolos beasiswa');
});

test('the daily quota is reserved, given back on failure, and resets the next day', function () {
    Carbon::setTestNow('2026-11-25 09:00:00');
    $user = iqMentorPaid($this->user);
    $calls = 0;

    IqMentorAgent::fake(function () use (&$calls) {
        if ($calls++ === 0) {
            throw RateLimitedException::forProvider('openrouter');
        }

        return 'Jawaban mentor.';
    });

    $quota = app(IqMentorQuotaServiceInterface::class);
    $chat = Livewire::actingAs($user)->test(MentorChat::class, ['attempt_id' => $this->attempt->id]);

    $chat->set('question', 'Strategi soal logika?')->call('send');
    expect($quota->remaining($user))->toBe(2);

    $chat->set('question', 'Strategi soal logika?')->call('send');
    $chat->set('question', 'Strategi soal numerik?')->call('send');
    expect($quota->remaining($user))->toBe(0);

    $chat->set('question', 'Satu lagi?')->call('send')->assertHasErrors('question');

    Carbon::setTestNow('2026-11-26 09:00:00');
    expect($quota->remaining($user))->toBe(2);
});

test('"Tanya mentor soal ini" anchors the chat to a question of the attempt', function () {
    $user = iqMentorPaid($this->user);
    IqMentorAgent::fake(['Jawaban.']);

    Livewire::actingAs($user)->test(MentorChat::class, ['attempt_id' => $this->attempt->id])
        ->dispatch('iq-ask-mentor', position: 2)
        ->assertSet('question_position', 2)
        ->call('send')
        ->assertSee('[Soal 2]');

    IqMentorAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'nomor 2'));
});

test('a running attempt has no mentor chat', function () {
    $user = iqMentorPaid(User::factory()->create());
    $running = app(IqAttemptServiceInterface::class)->start($user, IqTest::query()->firstOrFail());

    expect($running)->toBeInstanceOf(IqAttempt::class);

    Livewire::actingAs($user)->test(MentorChat::class, ['attempt_id' => $running->id])->assertForbidden();
});

<?php

use App\Contracts\IqAttemptServiceInterface;
use App\Enums\IqAttemptStatus;
use App\Http\Controllers\TryoutTheoryPdfController;
use App\Livewire\Iq\Index;
use App\Livewire\Iq\Material;
use App\Livewire\Iq\Result;
use App\Livewire\Iq\Runner;
use App\Models\IqAttempt;
use App\Models\IqQuestion;
use App\Models\IqTest;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config(['pricing.enabled' => true]);
    $this->user = User::factory()->create();
    $this->test = IqTest::factory()->withQuestions()->create();
    IqQuestion::query()->update(['explanation' => 'PEMBAHASAN-RAHASIA']);
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Give the mentee paid access (an active Pro plan unlocks iq_premium).
 */
function iqGivePaidAccess(User $user): User
{
    Subscription::query()->create(['user_id' => $user->id, 'plan' => 'pro_1bln', 'active_until' => now()->addMonth()]);

    return $user->fresh();
}

/**
 * Start an attempt, answer the first $correct questions with the key, and send it.
 */
function iqFinishedAttempt(User $user, IqTest $test, int $correct = 30): IqAttempt
{
    $attempts = app(IqAttemptServiceInterface::class);
    $attempt = $attempts->start($user, $test);

    foreach (range(1, $correct) as $position) {
        $attempts->answer($attempt, $position, 'A');
    }

    $attempts->finish($attempt);

    return $attempt->fresh();
}

test('the start page shows the test, the disclaimer and Mulai', function () {
    $this->actingAs($this->user)
        ->get(route('iq.index'))
        ->assertOk()
        ->assertSee('Tes IQ Latihan')
        ->assertSee('35')
        ->assertSee('Ini skor latihan, bukan skor IQ resmi. Hasil ini tidak dapat dipakai sebagai syarat beasiswa.')
        ->assertSee('data-test="iq-start"', false);
});

test('guests are sent to login', function () {
    $this->get(route('iq.index'))->assertRedirect(route('login'));
});

test('Mulai starts an attempt and opens the runner; a second click resumes it', function () {
    $this->actingAs($this->user);

    Livewire::test(Index::class)->call('start')->assertRedirect();

    $attempt = IqAttempt::query()->firstOrFail();

    Livewire::test(Index::class)->call('start')->assertRedirect(route('iq.runner', $attempt));

    expect(IqAttempt::query()->count())->toBe(1);
});

test('the runner shows one question with A-D buttons, the timer and never the key', function () {
    $attempt = app(IqAttemptServiceInterface::class)->start($this->user, $this->test);

    $this->actingAs($this->user)
        ->get(route('iq.runner', $attempt))
        ->assertOk()
        ->assertSee('Soal 1 dari 35')
        ->assertSee('Numerik')
        ->assertSee('data-test="iq-option-D"', false)
        ->assertSee('data-test="tryout-timer"', false)
        ->assertDontSee('PEMBAHASAN-RAHASIA')
        ->assertDontSee('answer_key');
});

test('visuospatial questions show their image with alt text', function () {
    $attempt = app(IqAttemptServiceInterface::class)->start($this->user, $this->test);
    $this->actingAs($this->user);

    Livewire::test(Runner::class, ['attempt' => $attempt])
        ->call('goTo', 8)
        ->assertSee('Visuospasial')
        ->assertSeeHtml('data-test="iq-image"')
        ->assertSeeHtml('alt="Pola bentuk tiga baris dengan satu kotak kosong."');
});

test('picking saves the answer, moves the progress, and the support message is dispatched at 25%', function () {
    $attempt = app(IqAttemptServiceInterface::class)->start($this->user, $this->test);
    $this->actingAs($this->user);

    $runner = Livewire::test(Runner::class, ['attempt' => $attempt]);

    foreach (range(1, 8) as $position) {
        $runner->call('goTo', $position)->call('choose', 'B')->assertNotDispatched('iq-support');
    }

    $runner->call('goTo', 9)
        ->call('choose', 'A')
        ->assertDispatched('iq-support', message: 'Kamu sudah menjawab 9 dari 35 soal. Di bagian Visuospasial, kamu sudah menjawab 2 dari 7 soal.')
        ->assertSee('9 dari 35 dijawab');

    expect($attempt->answers()->whereNotNull('choice')->count())->toBe(9);
});

test('Kirim closes the attempt and opens the result', function () {
    $attempt = app(IqAttemptServiceInterface::class)->start($this->user, $this->test);
    $this->actingAs($this->user);

    Livewire::test(Runner::class, ['attempt' => $attempt])
        ->call('choose', 'A')
        ->call('submit')
        ->assertRedirect(route('iq.result', $attempt));

    expect($attempt->fresh()->status)->toBe(IqAttemptStatus::Completed);
});

test('when the timer ends the saved answers are sent automatically', function () {
    Carbon::setTestNow('2026-11-25 10:00:00');
    $attempt = app(IqAttemptServiceInterface::class)->start($this->user, $this->test);
    $this->actingAs($this->user);

    $runner = Livewire::test(Runner::class, ['attempt' => $attempt])->call('choose', 'A');

    Carbon::setTestNow('2026-11-25 10:21:00');
    $runner->call('expire')->assertRedirect(route('iq.result', $attempt));

    $attempt->refresh();

    expect($attempt->status)->toBe(IqAttemptStatus::Expired)
        ->and($attempt->answered_count)->toBe(1);
});

test('a free account sees only the answered count; no score, key or explanation reaches the page', function () {
    $attempt = iqFinishedAttempt($this->user, $this->test);

    $this->actingAs($this->user)
        ->get(route('iq.result', $attempt))
        ->assertOk()
        ->assertSee('Kamu sudah mengerjakan 30 dari 35 soal.')
        ->assertSee('Lihat skor dan pembahasan dengan membuka akses')
        ->assertSee('Ini skor latihan, bukan skor IQ resmi.')
        ->assertDontSee('data-test="iq-score"', false)
        ->assertDontSee('86%')
        ->assertDontSee('Sangat baik')
        ->assertDontSee('PEMBAHASAN-RAHASIA')
        ->assertDontSee('Jawaban benar');
});

test('paid access shows the score per subtest, the category, the review and the disclaimer, never an IQ number', function () {
    $user = iqGivePaidAccess($this->user);
    $attempt = iqFinishedAttempt($user, $this->test, 30);

    $response = $this->actingAs($user)->get(route('iq.result', $attempt));

    $response->assertOk()
        ->assertSee('86%')
        ->assertSee('Sangat baik')
        ->assertSee('7/7 (100%)')
        ->assertSee('3/8 (38%)')
        ->assertSee('PEMBAHASAN-RAHASIA')
        ->assertSee('Mentor AI Tes IQ')
        ->assertSee('Ini skor latihan, bukan skor IQ resmi. Hasil ini tidak dapat dipakai sebagai syarat beasiswa.');

    expect($response->getContent())->not->toMatch('/\bIQ\s*(kamu|mu|:)?\s*\d{2,3}\b/i')
        ->not->toContain('skor IQ kamu');
});

test('another mentee cannot open someone else\'s runner or result', function () {
    $attempt = iqFinishedAttempt($this->user, $this->test);
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('iq.result', $attempt))->assertForbidden();
    $this->actingAs($other)->get(route('iq.runner', $attempt))->assertForbidden();
});

test('a running attempt redirects from the result page back to the runner', function () {
    $attempt = app(IqAttemptServiceInterface::class)->start($this->user, $this->test);
    $this->actingAs($this->user);

    Livewire::test(Result::class, ['attempt' => $attempt])->assertRedirect(route('iq.runner', $attempt));
});

test('the material is locked without paid access and its PDF is never served to free accounts', function () {
    Storage::fake('local');
    Storage::disk('local')->put('iq-material/materi.pdf', '%PDF-1.4 materi');
    $this->test->update(['material_pdf_path' => 'iq-material/materi.pdf']);

    $this->actingAs($this->user);

    Livewire::test(Material::class)->assertSeeHtml('data-test="iq-material-locked"')->assertDontSeeHtml('data-test="iq-material-viewer"');

    $this->get(route('iq.material.pdf'), [TryoutTheoryPdfController::VIEWER_HEADER => '1'])->assertForbidden();
});

test('paid access reads the material only through the viewer, masked and uncached', function () {
    Storage::fake('local');
    Storage::disk('local')->put('iq-material/materi.pdf', '%PDF-1.4 materi');
    $this->test->update(['material_pdf_path' => 'iq-material/materi.pdf']);
    $user = iqGivePaidAccess($this->user);

    $this->actingAs($user);

    Livewire::test(Material::class)->assertSeeHtml('data-test="iq-material-viewer"');

    $this->get(route('iq.material.pdf'))->assertNotFound();

    $response = $this->get(route('iq.material.pdf'), [TryoutTheoryPdfController::VIEWER_HEADER => '1'])->assertOk();
    $key = base64_decode((string) $response->headers->get(TryoutTheoryPdfController::KEY_HEADER));
    $body = (string) $response->getContent();

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($body)->not->toContain('%PDF')
        ->and($body ^ substr(str_repeat($key, intdiv(strlen($body), strlen($key)) + 1), 0, strlen($body)))->toBe('%PDF-1.4 materi');
});

test('the sidebar links to the Tes IQ', function () {
    $this->actingAs($this->user)->get(route('iq.index'))->assertSee(route('iq.index'));
});

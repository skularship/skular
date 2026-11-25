<?php

use App\Ai\Agents\SpeakingAssessmentAgent;
use App\Ai\Agents\WritingAssessmentAgent;
use App\Contracts\TryoutAnswerServiceInterface;
use App\Contracts\TryoutAttemptServiceInterface;
use App\Enums\AiFeedbackStatus;
use App\Enums\ModuleCategory;
use App\Enums\TryoutAttemptStatus;
use App\Enums\TryoutMode;
use App\Enums\TryoutSkill;
use App\Http\Controllers\TryoutTheoryPdfController;
use App\Livewire\Tryout\Index;
use App\Livewire\Tryout\Result;
use App\Livewire\Tryout\Review;
use App\Livewire\Tryout\Runner;
use App\Livewire\Tryout\Sections\ObjectiveSection;
use App\Livewire\Tryout\Show;
use App\Models\Module;
use App\Models\Subscription;
use App\Models\Tryout;
use App\Models\TryoutAttempt;
use App\Models\TryoutQuestion;
use App\Models\User;
use App\Services\ContentCache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config(['pricing.enabled' => true]);
    $this->user = User::factory()->create();
    $this->tryout = Tryout::factory()->withIeltsSections(question_count: 4, units: 2, per_level: 2)->create(['title' => 'Try Out IELTS Academic 1']);
});

/**
 * Give the mentee an active Pro plan.
 */
function makePro(User $user): User
{
    Subscription::query()->create(['user_id' => $user->id, 'plan' => 'pro_1bln', 'active_until' => now()->addMonth()]);

    return $user->fresh();
}

/**
 * Finish Listening and Reading with every answer correct, then close Writing and Speaking.
 */
function finishAttempt(User $user, Tryout $tryout): TryoutAttempt
{
    $attempts = app(TryoutAttemptServiceInterface::class);
    $attempt = $attempts->start($user, $tryout, TryoutMode::Practice);
    $answers = app(TryoutAnswerServiceInterface::class);

    foreach ([TryoutSkill::Listening, TryoutSkill::Reading] as $skill) {
        $attempts->startSection($attempt->fresh());
        $section = $attempt->sections()->where('skill', $skill->value)->first();

        while (($view = $answers->currentQuestion($section->fresh())) !== null) {
            $key = TryoutQuestion::query()->where('code', $view->code)->value('answer_key');
            $answers->answerObjective($section->fresh(), $view->code, $view->sequence === 1 ? 'A' : $key);
        }
    }

    foreach ([TryoutSkill::Writing, TryoutSkill::Speaking] as $skill) {
        $attempts->startSection($attempt->fresh());
        $attempts->submitSection($attempt->sections()->where('skill', $skill->value)->first());
    }

    return $attempt->fresh();
}

test('the try out list shows published try outs only', function () {
    Tryout::factory()->draft()->withIeltsSections()->create(['title' => 'Draf Rahasia']);

    $this->actingAs($this->user)->get(route('tryout.index'))
        ->assertOk()
        ->assertSee('Try Out IELTS Academic 1')
        ->assertDontSee('Draf Rahasia');
});

test('a draft try out page is not found', function () {
    $draft = Tryout::factory()->draft()->create();

    $this->actingAs($this->user)->get(route('tryout.show', $draft))->assertNotFound();
});

test('starting from the intro page opens the runner', function () {
    Livewire::actingAs($this->user)
        ->test(Show::class, ['tryout' => $this->tryout])
        ->set('mode', 'exam')
        ->call('start')
        ->assertRedirect(route('tryout.runner', $this->user->tryoutAttempts()->first()));

    expect($this->user->tryoutAttempts()->first()->mode)->toBe(TryoutMode::Exam);
});

test('another mentee cannot open someone else\'s runner or result', function () {
    $attempt = app(TryoutAttemptServiceInterface::class)->start($this->user, $this->tryout, TryoutMode::Practice);
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('tryout.runner', $attempt))->assertForbidden();
    $this->actingAs($other)->get(route('tryout.result', $attempt))->assertForbidden();
});

test('the runner and the question component never contain the key or the explanation', function () {
    $attempt = app(TryoutAttemptServiceInterface::class)->start($this->user, $this->tryout, TryoutMode::Practice);

    $this->actingAs($this->user)->get(route('tryout.runner', $attempt))
        ->assertOk()
        ->assertDontSee('RAHASIA-PEMBAHASAN')
        ->assertDontSee('RAHASIA-TRANSKRIP');

    $component = Livewire::actingAs($this->user)
        ->test(ObjectiveSection::class, ['attempt_section_id' => $attempt->sections->first()->id]);

    $question = $component->get('question');
    $snapshot = json_encode($component->snapshot);

    expect($question)->not->toHaveKey('answer_key')
        ->and($question)->not->toHaveKey('explanation')
        ->and($snapshot)->not->toContain('RAHASIA-PEMBAHASAN')
        ->and($snapshot)->not->toContain('RAHASIA-TRANSKRIP')
        ->and($snapshot)->not->toContain('answer_key');
});

test('sending an answer through the component moves to the next question', function () {
    $attempt = app(TryoutAttemptServiceInterface::class)->start($this->user, $this->tryout, TryoutMode::Practice);

    $component = Livewire::actingAs($this->user)
        ->test(ObjectiveSection::class, ['attempt_section_id' => $attempt->sections->first()->id]);

    $first = $component->get('question')['code'];

    $component->set('answer', 'B')->call('submit')->assertHasNoErrors();

    expect($component->get('question')['code'])->not->toBe($first)
        ->and($component->get('answered'))->toBe(1);
});

test('an empty answer is refused', function () {
    $attempt = app(TryoutAttemptServiceInterface::class)->start($this->user, $this->tryout, TryoutMode::Practice);

    Livewire::actingAs($this->user)
        ->test(ObjectiveSection::class, ['attempt_section_id' => $attempt->sections->first()->id])
        ->call('submit')
        ->assertHasErrors('answer');
});

test('the result shows bands as numbers with the estimate label', function () {
    $attempt = finishAttempt($this->user, $this->tryout);

    $this->actingAs($this->user)->get(route('tryout.result', $attempt))
        ->assertOk()
        ->assertSee('Estimasi latihan')
        ->assertSee('3 dari 4 benar')
        ->assertSee('tidak berafiliasi dengan IELTS');
});

test('module pages still use labels and never show try out bands', function () {
    finishAttempt($this->user, $this->tryout);

    $this->actingAs($this->user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('band overall');
});

test('gratis gets the locked review without any key or explanation', function () {
    $attempt = finishAttempt($this->user, $this->tryout);

    $this->actingAs($this->user)->get(route('tryout.review', $attempt))
        ->assertOk()
        ->assertSee('Pembahasan tersedia untuk paket Pro')
        ->assertDontSee('RAHASIA-PEMBAHASAN');

    $snapshot = json_encode(Livewire::actingAs($this->user)->test(Review::class, ['attempt' => $attempt])->snapshot);

    expect($snapshot)->not->toContain('RAHASIA-PEMBAHASAN');
});

test('pro sees the key and the explanation of wrong answers', function () {
    $pro = makePro($this->user);
    $attempt = finishAttempt($pro, $this->tryout);

    $this->actingAs($pro)->get(route('tryout.review', $attempt))
        ->assertOk()
        ->assertSee('RAHASIA-PEMBAHASAN')
        ->assertSee('Tanya mentor soal ini');
});

test('with premium switched off nobody gets the review', function () {
    $pro = makePro($this->user);
    $attempt = finishAttempt($pro, $this->tryout);
    config(['pricing.enabled' => false]);

    $this->actingAs($pro)->get(route('tryout.review', $attempt))
        ->assertOk()
        ->assertDontSee('RAHASIA-PEMBAHASAN');
});

test('the try out list renders for a mentee with a completed attempt', function () {
    finishAttempt($this->user, $this->tryout);

    Livewire::actingAs($this->user)->test(Index::class)->assertSee('Lihat hasil');
});

test('a theory module is hidden from the library and the module player', function () {
    $theory = Module::factory()->create([
        'title' => 'Teori TFNG Rahasia',
        'category' => ModuleCategory::Perpustakaan,
        'is_tryout_theory' => true,
    ]);
    Module::factory()->create(['title' => 'Modul Perpustakaan Biasa', 'category' => ModuleCategory::Perpustakaan]);
    ContentCache::bump();

    $this->actingAs($this->user)->get(route('library.index'))
        ->assertOk()
        ->assertSee('Modul Perpustakaan Biasa')
        ->assertDontSee('Teori TFNG Rahasia');

    $this->actingAs($this->user)->get(route('modules.show', $theory))->assertNotFound();
});

test('the theory page is pro only and the materials link is for everyone', function () {
    Storage::fake('local');
    Storage::disk('local')->put('tryout-theory/teori.pdf', '%PDF-1.4 materi rahasia');

    $theory = Module::factory()->create(['title' => 'Strategi TFNG', 'category' => ModuleCategory::Perpustakaan, 'is_tryout_theory' => true]);
    $this->tryout->update([
        'theory_module_id' => $theory->id,
        'theory_pdf_path' => 'tryout-theory/teori.pdf',
        'materials_url' => 'https://contoh.test/materi',
    ]);

    $this->actingAs($this->user)->get(route('tryout.theory', $this->tryout))
        ->assertOk()
        ->assertSee('Teori IELTS tersedia untuk paket Pro')
        ->assertSee('PDF teori bisa dibaca dengan paket Pro')
        ->assertSee('Beli materi lengkap (PDF)')
        ->assertDontSee('Strategi TFNG')
        ->assertDontSee('data-test="theory-pdf-viewer"', false)
        ->assertDontSee(route('tryout.theory.pdf', $this->tryout));

    $pro = makePro($this->user);

    $this->actingAs($pro)->get(route('tryout.theory', $this->tryout))
        ->assertOk()
        ->assertSee('Strategi TFNG')
        ->assertSee('data-test="theory-pdf-viewer"', false)
        ->assertSee($pro->email)
        ->assertDontSee('Unduh PDF');
});

test('the theory pdf is only served masked to the in-page viewer of a pro mentee', function () {
    Storage::fake('local');
    $pdf = '%PDF-1.4 materi rahasia '.str_repeat('x', 100);
    Storage::disk('local')->put('tryout-theory/teori.pdf', $pdf);
    $this->tryout->update(['theory_pdf_path' => 'tryout-theory/teori.pdf']);
    $viewer = [TryoutTheoryPdfController::VIEWER_HEADER => '1'];

    // Gratis never gets the bytes.
    $this->actingAs($this->user)->get(route('tryout.theory.pdf', $this->tryout), $viewer)->assertForbidden();

    $pro = makePro($this->user);

    // Opening the URL directly (no viewer header) shows nothing to save.
    $this->actingAs($pro)->get(route('tryout.theory.pdf', $this->tryout))->assertNotFound();

    $response = $this->actingAs($pro)->get(route('tryout.theory.pdf', $this->tryout), $viewer)->assertOk();
    $key = base64_decode((string) $response->headers->get(TryoutTheoryPdfController::KEY_HEADER));
    $body = $response->getContent();

    expect($body)->not->toContain('%PDF')
        ->and($body)->not->toContain('materi rahasia')
        ->and($response->headers->get('Content-Disposition'))->toBeNull()
        ->and($response->headers->get('Content-Type'))->toBe('application/octet-stream')
        ->and((string) $response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($body ^ substr(str_repeat($key, 10), 0, strlen($body)))->toBe($pdf);

    $this->tryout->update(['theory_pdf_path' => null]);
    $this->actingAs($pro)->get(route('tryout.theory.pdf', $this->tryout), $viewer)->assertNotFound();
});

test('a finished attempt sends the runner to the result page', function () {
    $attempt = finishAttempt($this->user, $this->tryout);

    Livewire::actingAs($this->user)->test(Runner::class, ['attempt' => $attempt])
        ->assertRedirect(route('tryout.result', $attempt));
});

test('the result page runs pending writing assessment one task per request', function () {
    WritingAssessmentAgent::fake([
        ['task' => ['band' => 6.5, 'reason' => 'Jelas.'], 'coherence' => ['band' => 6, 'reason' => 'Rapi.'], 'lexical' => ['band' => 6, 'reason' => 'Cukup.'], 'grammar' => ['band' => 5.5, 'reason' => 'Ada salah.'], 'summary' => 'Bagus.'],
    ])->preventStrayPrompts();
    SpeakingAssessmentAgent::fake()->preventStrayPrompts();

    $attempts = app(TryoutAttemptServiceInterface::class);
    $answers = app(TryoutAnswerServiceInterface::class);
    $attempt = $attempts->start($this->user, $this->tryout, TryoutMode::Practice);

    foreach (['listening', 'reading'] as $skill) {
        $attempts->submitSection($attempt->sections()->where('skill', $skill)->first());
        $attempts->startSection($attempt->fresh());
    }

    $writing = $attempt->sections()->where('skill', 'writing')->first();
    $prompts = $answers->productivePrompts($writing);
    $answers->saveEssay($writing, $prompts[0]->code, str_repeat('word ', 160));
    $attempts->submitSection($writing);
    $attempts->startSection($attempt->fresh());
    $attempts->submitSection($attempt->sections()->where('skill', 'speaking')->first());

    expect($attempt->fresh()->status)->toBe(TryoutAttemptStatus::Completed);

    $component = Livewire::actingAs($this->user)->test(Result::class, ['attempt' => $attempt->fresh()]);
    $component->assertOk();
    $component->call('advanceAssessment');

    $task_one = $writing->answers()->orderBy('sequence')->first();

    expect($task_one->band)->toBe(6.0)
        ->and($task_one->ai_status)->toBe(AiFeedbackStatus::Completed);

    // Task 2 is empty: graded 0 without calling the model.
    $component->call('advanceAssessment');
    expect($writing->fresh()->band)->not->toBeNull();
});

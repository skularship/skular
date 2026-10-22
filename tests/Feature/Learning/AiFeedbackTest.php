<?php

use App\Ai\Agents\PracticeFeedbackAgent;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Exceptions\AiFeedbackException;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;

beforeEach(function () {
    $this->fase = Fase::factory()->create(['fase_order' => 1]);
    $this->module = Module::factory()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 1,
        'title' => 'Modul Esai Motivasi',
        'goal' => 'Menulis esai motivasi yang kuat.',
        'summary_material' => 'Esai motivasi memuat alasan, bukti, dan rencana.',
        'ai_knowledge' => 'Rubrik: gunakan struktur STAR dan tunjukkan dampak nyata.',
    ]);
    $this->form = PracticeForm::factory()->create([
        'module_id' => $this->module->id,
        'title' => 'Latihan Esai',
        'description' => 'Tulis esai motivasi singkat.',
    ]);
    $this->mentee = User::factory()->profiled()->create();
    $this->service = app(AiFeedbackServiceInterface::class);
});

/**
 * Build a well-formed structured feedback payload.
 *
 * @return array<string, mixed>
 */
function feedbackPayload(int $score = 82): array
{
    return [
        'score' => $score,
        'strengths' => ['Struktur STAR terlihat jelas.'],
        'improvements' => ['Tambahkan angka dampak.'],
        'feedback' => 'Jawabanmu sudah kuat dan runtut.',
        'next_steps' => ['Tulis satu paragraf dampak terukur.'],
    ];
}

test('the feedback prompt carries the module rubric and the mentee answer', function () {
    PracticeFeedbackAgent::fake([feedbackPayload()])->preventStrayPrompts();

    $this->service->generate($this->module, $this->form, ['jawaban' => 'Saya pernah memimpin 20 anggota.']);

    PracticeFeedbackAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Modul Esai Motivasi')
        && str_contains($prompt->prompt, 'Menulis esai motivasi yang kuat.')
        && str_contains($prompt->prompt, 'struktur STAR')
        && str_contains($prompt->prompt, 'Tulis esai motivasi singkat.')
        && str_contains($prompt->prompt, 'Saya pernah memimpin 20 anggota.'));
});

test('structured feedback is mapped onto the feedback dto', function () {
    PracticeFeedbackAgent::fake([feedbackPayload(82)]);

    $feedback = $this->service->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']);

    expect($feedback->score)->toBe(82)
        ->and($feedback->strengths)->toBe(['Struktur STAR terlihat jelas.'])
        ->and($feedback->improvements)->toBe(['Tambahkan angka dampak.'])
        ->and($feedback->feedback)->toBe('Jawabanmu sudah kuat dan runtut.')
        ->and($feedback->next_steps)->toBe(['Tulis satu paragraf dampak terukur.']);
});

test('an out-of-range score is clamped by the dto', function (int $score, int $normalized) {
    PracticeFeedbackAgent::fake([feedbackPayload($score)]);

    $feedback = $this->service->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']);

    expect($feedback->normalizedScore())->toBe($normalized);
})->with([
    'above range' => [150, 100],
    'below range' => [-10, 0],
    'in range' => [60, 60],
]);

test('a non-structured ai response is rejected', function () {
    PracticeFeedbackAgent::fake(['bukan json sama sekali']);

    expect(fn () => $this->service->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']))
        ->toThrow(AiFeedbackException::class);
});

test('a provider failure is surfaced as a feedback exception', function () {
    PracticeFeedbackAgent::fake(fn () => throw new RuntimeException('provider down'));

    expect(fn () => $this->service->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']))
        ->toThrow(AiFeedbackException::class);
});

test('a failed evaluation keeps the submission and marks it for retry', function () {
    PracticeFeedbackAgent::fake(fn () => throw new RuntimeException('provider down'));

    $response = app(PracticeSubmissionServiceInterface::class)->submit(
        $this->mentee,
        $this->form,
        ['jawaban' => 'Jawaban yang harus tetap aman.'],
    );

    expect($response->fresh()->answers['jawaban'])->toContain('Jawaban yang harus tetap aman.')
        ->and($response->ai_status->value)->toBe('failed')
        ->and($response->ai_error)->not->toBeNull()
        ->and($response->ai_score)->toBeNull();
});

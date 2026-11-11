<?php

use App\Ai\Agents\PracticeFeedbackAgent;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Exceptions\AiFeedbackException;
use App\Jobs\EvaluatePracticeFeedback;
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
        'strengths' => [
            ['point' => 'Struktur STAR jelas', 'explanation' => 'Situasi, tugas, aksi, dan hasil muncul berurutan.'],
        ],
        'improvements' => [
            ['point' => 'Dampak belum terukur', 'explanation' => 'Tambahkan angka supaya hasilnya meyakinkan.'],
        ],
        'feedback' => 'Jawabanmu sudah kuat dan runtut.',
        'next_steps' => [
            ['point' => 'Tulis paragraf dampak', 'explanation' => 'Satu paragraf berisi hasil terukur dari aksimu.'],
        ],
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
        ->and($feedback->strengths[0]->point)->toBe('Struktur STAR jelas')
        ->and($feedback->strengths[0]->explanation)->toBe('Situasi, tugas, aksi, dan hasil muncul berurutan.')
        ->and($feedback->improvements[0]->point)->toBe('Dampak belum terukur')
        ->and($feedback->feedback)->toBe('Jawabanmu sudah kuat dan runtut.')
        ->and($feedback->next_steps[0]->explanation)->toBe('Satu paragraf berisi hasil terukur dari aksimu.')
        ->and($feedback->toArray()['strengths'][0])->toBe([
            'point' => 'Struktur STAR jelas',
            'explanation' => 'Situasi, tugas, aksi, dan hasil muncul berurutan.',
        ]);
});

test('a model answering with legacy plain strings is still accepted as explanations', function () {
    PracticeFeedbackAgent::fake([[
        'score' => 70,
        'strengths' => ['Struktur STAR terlihat jelas.'],
        'improvements' => ['Tambahkan angka dampak.'],
        'feedback' => 'Sudah cukup baik.',
        'next_steps' => ['Tulis satu paragraf dampak terukur.'],
    ]])->preventStrayPrompts();

    $feedback = $this->service->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']);

    expect($feedback->strengths[0]->point)->toBeNull()
        ->and($feedback->strengths[0]->explanation)->toBe('Struktur STAR terlihat jelas.')
        ->and($feedback->next_steps)->toHaveCount(1);
});

test('malformed feedback items are dropped and markdown is stripped from points', function () {
    PracticeFeedbackAgent::fake([[
        'score' => 75,
        'strengths' => [
            ['point' => '**Pembuka kuat**:', 'explanation' => 'Kalimat pertama langsung menarik.'],
            ['point' => '', 'explanation' => ''],
            42,
            null,
            ['unexpected' => 'shape'],
        ],
        'improvements' => 'bukan daftar',
        'feedback' => 'Bagus.',
        'next_steps' => array_fill(0, 6, ['point' => 'Langkah', 'explanation' => 'Lakukan ini.']),
    ]])->preventStrayPrompts();

    $feedback = $this->service->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']);

    expect($feedback->strengths)->toHaveCount(2)
        ->and($feedback->strengths[0]->point)->toBe('Pembuka kuat')
        ->and($feedback->strengths[1]->explanation)->toBe('42')
        ->and($feedback->improvements)->toBe([])
        ->and($feedback->next_steps)->toHaveCount(4);
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

    // The answers survive the failed provider call — the evaluation now runs
    // in the queue, so the status first shows the work as safely stored.
    expect($response->fresh()->answers['jawaban'])->toContain('Jawaban yang harus tetap aman.')
        ->and($response->ai_status->value)->toBe('processing');

    // Running the queued evaluation against the failing provider must mark
    // the stored submission as failed so the mentee can retry explicitly.
    (new EvaluatePracticeFeedback($response->id, $this->form->id))
        ->handle(app(PracticeSubmissionServiceInterface::class));

    $response->refresh();

    expect($response->answers['jawaban'])->toContain('Jawaban yang harus tetap aman.')
        ->and($response->ai_status->value)->toBe('failed')
        ->and($response->ai_error)->not->toBeNull()
        ->and($response->ai_score)->toBeNull();
});

test('long material and answers are shortened at a sentence end and marked as cut', function () {
    $sentence = 'Kamu menulis tujuan studi yang jelas dan terukur. ';
    $long = str_repeat($sentence, 200);

    $excerpt = PracticeFeedbackAgent::excerpt($long, 3000);

    expect(mb_strlen($excerpt))->toBeLessThanOrEqual(3000)
        ->and($excerpt)->toEndWith('terukur. [dipotong]')
        ->and(PracticeFeedbackAgent::excerpt('Jawaban pendek.', 3000))->toBe('Jawaban pendek.');
});

test('text without sentence ends is cut after a whole word', function () {
    $excerpt = PracticeFeedbackAgent::excerpt(str_repeat('kata ', 100), 60);

    expect(mb_strlen($excerpt))->toBeLessThanOrEqual(60)
        ->and($excerpt)->toEndWith('kata [dipotong]');
});

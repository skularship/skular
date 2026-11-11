<?php

use App\Enums\AiFeedbackStatus;
use App\Filament\Resources\UserPracticeResponses\Tables\UserPracticeResponsesTable;
use App\Livewire\Learning\ModulePlayer;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Livewire\Livewire;

beforeEach(function () {
    $fase = Fase::factory()->create(['fase_order' => 1]);
    $this->module = Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 1]);
    $this->form = PracticeForm::factory()->create(['module_id' => $this->module->id]);
    $this->mentee = User::factory()->profiled()->create();
});

test('structured feedback shows each main point in bold above its explanation', function () {
    UserPracticeResponse::factory()->withFeedback(80)->create([
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
    ]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSeeHtml('data-test="ai-feedback-strengths"')
        ->assertSeeHtml('<strong class="font-semibold text-zinc-900">Alur jawaban runtut</strong>')
        ->assertSee('Setiap paragraf menyambung ke paragraf berikutnya.')
        ->assertSeeHtml('<strong class="font-semibold text-zinc-900">Perkuat penutup</strong>')
        ->assertSeeHtml('data-test="ai-score-label"');
});

test('legacy plain-string feedback still renders as explanations without a bold point', function () {
    UserPracticeResponse::factory()->withLegacyFeedback(80)->create([
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
    ]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSee('Struktur jawaban sudah runtut.')
        ->assertSee('Tambahkan contoh konkret.')
        ->assertSee('Perkuat bagian penutup.')
        ->assertDontSeeHtml('<strong class="font-semibold text-zinc-900">');
});

test('the feedback summary is split into separate paragraphs', function () {
    UserPracticeResponse::factory()->create([
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 60,
        'ai_feedback' => [
            'feedback' => "Paragraf pertama.\n\nParagraf kedua.",
            'strengths' => [],
            'improvements' => [],
            'next_steps' => [],
        ],
    ]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSeeHtml('<p class="whitespace-pre-line">Paragraf pertama.</p>')
        ->assertSeeHtml('<p class="whitespace-pre-line">Paragraf kedua.</p>');
});

test('the admin view formats both feedback shapes', function () {
    $structured = UserPracticeResponsesTable::formatFeedback([
        'strengths' => [['point' => 'Alur runtut', 'explanation' => 'Paragraf saling menyambung.']],
    ]);
    $legacy = UserPracticeResponsesTable::formatFeedback(['strengths' => ['Struktur sudah runtut.']]);

    expect($structured)->toContain('• Alur runtut — Paragraf saling menyambung.')
        ->and($legacy)->toContain('• Struktur sudah runtut.');
});

<?php

use App\Contracts\ProductTourServiceInterface;
use App\Enums\AiFeedbackStatus;
use App\Enums\ProductTour;
use App\Livewire\Learning\ModulePlayer;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $fase = Fase::factory()->create(['fase_order' => 1]);

    // The module being played is first in the path (unlocked); the source
    // module only has to be published for its answers to be referenced.
    $this->module = Module::factory()->create([
        'fase_id' => $fase->id,
        'modul_order' => 1,
        'title' => 'Esai Khusus Beasiswa',
    ]);

    $this->source_module = Module::factory()->create([
        'fase_id' => $fase->id,
        'modul_order' => 2,
        'title' => 'Menyusun Personal Branding',
    ]);

    $this->source = PracticeForm::factory()->includeInCv(1)->create([
        'module_id' => $this->source_module->id,
        'title' => 'Pengalaman Organisasi',
        'cv_button_label' => 'pengalamanku',
        'fields' => [
            ['name' => 'peran', 'label' => 'Peran / Jabatan', 'type' => 'text', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []],
            ['name' => 'deskripsi', 'label' => 'Deskripsi Singkat', 'type' => 'textarea', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []],
            ['name' => 'bukti', 'label' => 'Bukti kegiatan', 'type' => 'file', 'required' => false, 'placeholder' => null, 'help_text' => null, 'options' => []],
        ],
    ]);

    $this->practice = PracticeForm::factory()->create([
        'module_id' => $this->module->id,
        'title' => 'Draft Esai Beasiswa',
    ]);

    $this->mentee = User::factory()->profiled()->create();
});

/**
 * Store a graded CV answer for a mentee on the source form.
 *
 * @param  array<string, mixed>  $answers
 */
function storeCvAnswer(User $user, PracticeForm $form, array $answers): UserPracticeResponse
{
    return $user->practiceResponses()->create([
        'module_id' => $form->module_id,
        'practice_form_id' => $form->id,
        'answers' => $answers,
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 87,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);
}

/**
 * Cut the reference panel out of the rendered module page.
 */
function cvPanelHtml(string $html): string
{
    $start = strpos($html, 'data-test="cv-reference-panel"');
    $end = strpos($html, 'data-test="practice-form"');

    expect($start)->not->toBeFalse()->and($end)->not->toBeFalse();

    return substr($html, (int) $start, (int) $end - (int) $start);
}

test('the panel only appears on the praktik step of a module with cv sources', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertDontSeeHtml('data-test="cv-reference-panel"')
        ->assertDontSee('Tampilkan pengalamanku');

    $this->module->cvSources()->attach($this->source->id);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSeeHtml('data-test="cv-reference-panel"')
        ->assertSeeHtml('data-tour="cv-reference-panel"')
        ->assertSee('Data dirimu')
        ->assertSee('Tampilkan pengalamanku');

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'pahami'])
        ->assertDontSeeHtml('data-test="cv-reference-panel"');
});

test('a filled source shows labelled entries and never the uploaded file', function () {
    $this->module->cvSources()->attach($this->source->id);
    storeCvAnswer($this->mentee, $this->source, [
        'peran' => 'Ketua BEM 2025',
        'deskripsi' => "Memimpin 40 anggota.\nMenggalang dana bencana.",
        'bukti' => 'practice-uploads/1/sertifikat.pdf',
    ]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertDontSeeHtml('data-test="cv-source-entry"')
        ->call('toggleCvSource', $this->source->id)
        ->assertSet('open_cv_source', $this->source->id)
        ->assertSeeHtml('aria-expanded="true"')
        ->assertSeeHtml('data-test="cv-source-entry"')
        ->assertSeeHtml('data-test="cv-copy"')
        ->assertSee('Peran / Jabatan')
        ->assertSee('Ketua BEM 2025')
        ->assertSee('Deskripsi Singkat')
        ->assertSee('Ini jawaban yang sudah kamu isi sebelumnya.')
        ->assertSee('Perbaiki di modul asal')
        ->assertSeeHtml(e(route('modules.show', ['module' => $this->source_module->id, 'section' => 'praktik'])))
        ->assertDontSee('Bukti kegiatan')
        ->assertDontSee('practice-uploads/1/sertifikat.pdf')
        ->assertDontSee('peran')
        // Clicking again closes the card.
        ->call('toggleCvSource', $this->source->id)
        ->assertSet('open_cv_source', null)
        ->assertDontSeeHtml('data-test="cv-source-entry"');
});

test('only one source is open at a time', function () {
    $second = PracticeForm::factory()->includeInCv(2)->create([
        'module_id' => $this->source_module->id,
        'title' => 'Prestasi dan Penghargaan',
    ]);
    $this->module->cvSources()->attach([$this->source->id, $second->id]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->call('toggleCvSource', $this->source->id)
        ->call('toggleCvSource', $second->id)
        ->assertSet('open_cv_source', $second->id)
        ->assertSeeHtmlInOrder(['Tampilkan pengalamanku', 'Tampilkan Prestasi dan Penghargaan']);
});

test('an unanswered source tells the mentee where to fill it in', function () {
    $this->module->cvSources()->attach($this->source->id);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSee('Tampilkan pengalamanku')
        ->call('toggleCvSource', $this->source->id)
        ->assertSee('Kamu belum mengisi ini.')
        ->assertSee('Isi dulu di modul Menyusun Personal Branding')
        ->assertSeeHtml(e(route('modules.show', ['module' => $this->source_module->id, 'section' => 'praktik'])));
});

test('a mentee never sees the cv answers of another mentee', function () {
    $this->module->cvSources()->attach($this->source->id);
    $other_mentee = User::factory()->profiled()->create();

    storeCvAnswer($other_mentee, $this->source, ['peran' => 'Jabatan rahasia mentee A']);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->call('toggleCvSource', $this->source->id)
        ->assertSee('Kamu belum mengisi ini.')
        ->assertDontSee('Jabatan rahasia mentee A');

    storeCvAnswer($this->mentee, $this->source, ['peran' => 'Jabatan milik mentee B']);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->call('toggleCvSource', $this->source->id)
        ->assertSee('Jabatan milik mentee B')
        ->assertDontSee('Jabatan rahasia mentee A');
});

test('a form id that is not a source of this module opens nothing', function () {
    $this->module->cvSources()->attach($this->source->id);

    $unlinked = PracticeForm::factory()->includeInCv(3)->create([
        'module_id' => $this->source_module->id,
        'title' => 'Bagian Tidak Ditautkan',
        'fields' => [['name' => 'isi', 'label' => 'Isi', 'type' => 'text', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []]],
    ]);
    storeCvAnswer($this->mentee, $unlinked, ['isi' => 'Isi yang tidak boleh terbuka']);

    $component = Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->call('toggleCvSource', $this->source->id)
        ->call('toggleCvSource', $unlinked->id)
        ->assertSet('open_cv_source', null)
        ->assertDontSee('Isi yang tidak boleh terbuka')
        ->assertDontSee('Bagian Tidak Ditautkan')
        ->call('toggleCvSource', 999999)
        ->assertSet('open_cv_source', null);

    expect(fn () => $component->set('open_cv_source', $unlinked->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('sources whose form is inactive or whose module is a draft are hidden', function () {
    $draft_module = Module::factory()->draft()->create();
    $draft_source = PracticeForm::factory()->includeInCv(2)->create([
        'module_id' => $draft_module->id,
        'title' => 'Sumber Modul Draf',
    ]);
    $inactive_source = PracticeForm::factory()->includeInCv(3)->create([
        'module_id' => $this->source_module->id,
        'title' => 'Sumber Nonaktif',
        'is_active' => false,
    ]);
    $this->module->cvSources()->attach([$draft_source->id, $inactive_source->id]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertDontSeeHtml('data-test="cv-reference-panel"')
        ->call('toggleCvSource', $draft_source->id)
        ->assertSet('open_cv_source', null)
        ->assertDontSee('Sumber Modul Draf')
        ->assertDontSee('Sumber Nonaktif');
});

test('using the panel never fills answers, stores responses or opens the checkpoint', function () {
    $this->module->cvSources()->attach($this->source->id);
    storeCvAnswer($this->mentee, $this->source, ['peran' => 'Ketua BEM 2025']);
    $responses_before = UserPracticeResponse::query()->count();

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSet('answers', [])
        ->call('toggleCvSource', $this->source->id)
        ->call('toggleCvSource', $this->source->id)
        ->call('toggleCvSource', $this->source->id)
        ->assertSet('answers', [])
        ->call('goToSection', 'checkpoint')
        ->assertSet('section', 'praktik')
        ->assertSee('Simpan jawaban semua latihan praktik dulu sebelum masuk ke tahap akhir.');

    expect(UserPracticeResponse::query()->count())->toBe($responses_before)
        ->and(UserPracticeResponse::query()->where('practice_form_id', $this->practice->id)->exists())->toBeFalse();
});

test('the button falls back to the form title when no button label is set', function () {
    $this->source->update(['cv_button_label' => '   ']);
    $this->module->cvSources()->attach($this->source->id);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSee('Tampilkan Pengalaman Organisasi')
        ->assertDontSee('Tampilkan pengalamanku');
});

test('the panel shows no score or assessment label', function () {
    $this->module->cvSources()->attach($this->source->id);
    storeCvAnswer($this->mentee, $this->source, ['peran' => 'Ketua BEM 2025']);

    $html = Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->call('toggleCvSource', $this->source->id)
        ->html();

    $panel = cvPanelHtml($html);
    // Visible text only: icon paths contain digits that are not scores.
    $panel_text = strip_tags($panel);

    expect($panel_text)->toContain('Ketua BEM 2025')
        ->not->toContain('87')
        ->not->toContain('Skor')
        ->not->toContain('Sangat baik')
        ->not->toContain('Sudah dinilai')
        ->and($panel)->not->toContain('data-test="ai-score-label"');
});

test('the module tour points at the reference panel', function () {
    $targets = collect(app(ProductTourServiceInterface::class)->steps(ProductTour::Module))->pluck('target');

    expect($targets)->toContain('[data-tour="cv-reference-panel"]');
});

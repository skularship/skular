<?php

use App\Contracts\AiFeedbackServiceInterface;
use App\Data\AiFeedbackData;
use App\Enums\AiFeedbackStatus;
use App\Enums\CheckpointStatus;
use App\Exceptions\AiFeedbackException;
use App\Livewire\Learning\ModulePlayer;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\QuickQuestion;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->fase = Fase::factory()->create(['fase_order' => 1]);

    $this->module = Module::factory()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 1,
        'title' => 'Modul Pembuka Beasiswa',
    ]);

    $this->next_module = Module::factory()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 2,
        'title' => 'Modul Kedua',
    ]);

    $this->mentee = User::factory()->profiled()->create();
});

function learningStubFeedback(int $score): AiFeedbackServiceInterface
{
    return new class($score) implements AiFeedbackServiceInterface
    {
        public function __construct(private readonly int $score) {}

        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            return new AiFeedbackData(
                score: $this->score,
                strengths: ['Jawabanmu terstruktur dengan baik.'],
                improvements: ['Tambahkan contoh nyata.'],
                feedback: 'Jawaban yang baik, pertahankan strukturnya.',
                next_steps: ['Latih satu esai lagi.'],
            );
        }
    };
}

function learningFailingStubFeedback(): AiFeedbackServiceInterface
{
    return new class implements AiFeedbackServiceInterface
    {
        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            throw new AiFeedbackException('Layanan AI tidak dapat dihubungi.');
        }
    };
}

test('an unlocked module opens with its learning sections', function () {
    $this->actingAs($this->mentee)
        ->get(route('modules.show', $this->module))
        ->assertOk()
        ->assertSee('Modul Pembuka Beasiswa')
        ->assertSee('Mulai Modul')
        ->assertDontSee('<iframe', false);
});

test('a locked module cannot be opened', function () {
    $this->actingAs($this->mentee)
        ->get(route('modules.show', $this->next_module))
        ->assertForbidden();
});

test('starting the module marks it in progress', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module])
        ->call('start')
        ->assertSet('section', 'pahami');

    $checkpoint = $this->mentee->checkpoints()->where('module_id', $this->module->id)->first();

    expect($checkpoint->status)->toBe(CheckpointStatus::InProgress);
});

test('quick questions must all be answered before they are checked', function () {
    QuickQuestion::factory()->create(['module_id' => $this->module->id, 'answer' => true]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'quick'])
        ->call('checkQuickAnswers')
        ->assertHasErrors('quick_answers')
        ->assertSet('quick_checked', false);
});

test('quick questions are graded with an explanation', function () {
    $question = QuickQuestion::factory()->create([
        'module_id' => $this->module->id,
        'question' => 'Beasiswa hanya untuk mahasiswa berprestasi.',
        'answer' => false,
        'explanation' => 'Banyak beasiswa terbuka untuk semua latar belakang.',
    ]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'quick'])
        ->set('quick_answers', [(string) $question->id => '0'])
        ->call('checkQuickAnswers')
        ->assertSet('quick_checked', true)
        ->assertSee('Banyak beasiswa terbuka');
});

test('practice answers are validated against the dynamic form', function () {
    $form = PracticeForm::factory()->create(['module_id' => $this->module->id]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->call('submitPractice', $form->id)
        ->assertHasErrors('answers.'.$form->id.'.jawaban');
});

test('a practice submission is stored and receives structured ai feedback', function () {
    $form = PracticeForm::factory()->create(['module_id' => $this->module->id]);

    $this->app->instance(AiFeedbackServiceInterface::class, learningStubFeedback(88));

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->set('answers.'.$form->id.'.jawaban', 'Saya pernah menjadi ketua OSIS dan memimpin 20 anggota.')
        ->call('submitPractice', $form->id)
        ->assertHasNoErrors()
        ->assertSee('Feedback AI')
        ->assertSee('Jawaban yang baik, pertahankan strukturnya.');

    $response = $this->mentee->practiceResponses()->firstWhere('practice_form_id', $form->id);

    expect($response)->not->toBeNull()
        ->and($response->answers['jawaban'])->toContain('ketua OSIS')
        ->and($response->ai_score)->toBe(88)
        ->and($response->ai_status->value)->toBe('completed');
});

test('answers are prefilled when the mentee returns to a submitted form', function () {
    $form = PracticeForm::factory()->create(['module_id' => $this->module->id]);

    $this->mentee->practiceResponses()->create([
        'module_id' => $this->module->id,
        'practice_form_id' => $form->id,
        'answers' => ['jawaban' => 'Draft esai pertama saya.'],
        'ai_feedback' => [
            'feedback' => 'Esai sudah terstruktur dengan baik.',
            'strengths' => ['Pembukaan yang personal.'],
            'improvements' => [],
            'next_steps' => [],
        ],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 75,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSet('answers.'.$form->id.'.jawaban', 'Draft esai pertama saya.')
        ->assertSee('Feedback AI');
});

test('a failed ai request keeps the submission and offers a retry', function () {
    $form = PracticeForm::factory()->create(['module_id' => $this->module->id]);

    $this->app->instance(AiFeedbackServiceInterface::class, learningFailingStubFeedback());

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->set('answers.'.$form->id.'.jawaban', 'Jawaban yang saya tulis.')
        ->call('submitPractice', $form->id)
        ->assertHasNoErrors()
        ->assertSee('Feedback AI belum tersedia')
        ->assertSee('Coba lagi');

    $response = $this->mentee->practiceResponses()->firstWhere('practice_form_id', $form->id);

    expect($response->ai_status->value)->toBe('failed')
        ->and($response->answers['jawaban'])->toContain('Jawaban yang saya tulis');
});

test('confirming the checkpoint completes the module and unlocks the next one', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module])
        ->call('confirmCheckpoint')
        ->assertSet('module_completed', true);

    expect($this->mentee->checkpoints()->where('module_id', $this->module->id)->first()->status)
        ->toBe(CheckpointStatus::Completed);

    $next = $this->mentee->checkpoints()->where('module_id', $this->next_module->id)->first();

    expect($next->status)->toBe(CheckpointStatus::Available);
});

test('the checkpoint cannot be confirmed while practice is still pending', function () {
    PracticeForm::factory()->create(['module_id' => $this->module->id]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'checkpoint'])
        ->call('confirmCheckpoint')
        ->assertHasErrors('checkpoint')
        ->assertSet('module_completed', false);

    expect($this->mentee->checkpoints()->where('module_id', $this->module->id)->first()->status)
        ->toBe(CheckpointStatus::InProgress);
});

test('the checkpoint can be confirmed once every practice form is submitted', function () {
    $form = PracticeForm::factory()->create(['module_id' => $this->module->id]);

    $this->mentee->practiceResponses()->create([
        'module_id' => $this->module->id,
        'practice_form_id' => $form->id,
        'answers' => ['jawaban' => 'Jawaban saya.'],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 80,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'checkpoint'])
        ->call('confirmCheckpoint')
        ->assertSet('module_completed', true);

    expect($this->mentee->checkpoints()->where('module_id', $this->module->id)->first()->status)
        ->toBe(CheckpointStatus::Completed);
});

test('the module introduction appears in pahami after the material', function () {
    $this->module->update(['introduction' => 'Sebelum masuk kuis, baca ulang inti materi supaya jawabanmu tepat.']);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'pahami'])
        ->assertSee('Kata Pengantar')
        ->assertSee('Refleksi Menuju Kuis')
        ->assertSee('Sebelum masuk kuis, baca ulang inti materi supaya jawabanmu tepat.');
});

test('the module introduction appears before the practice forms', function () {
    PracticeForm::factory()->create(['module_id' => $this->module->id]);

    $this->module->update(['introduction' => 'Arahkan jawabanmu ke pengalaman organisasimu.']);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSee('Kata Pengantar')
        ->assertSee('Arahkan jawabanmu ke pengalaman organisasimu.')
        ->assertSee('Latihan praktik');
});

test('the module introduction is hidden when the admin left it empty', function () {
    $this->module->update(['introduction' => null]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'pahami'])
        ->assertDontSee('Kata Pengantar');
});

test('the module introduction only shows content of the active module', function () {
    PracticeForm::factory()->create(['module_id' => $this->module->id]);

    Module::whereKey($this->next_module->id)->update(['introduction' => 'Ini kata pengantar modul lain.']);
    $this->module->update(['introduction' => 'Ini kata pengantar modul aktif.']);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSet('section', 'praktik')
        ->assertSee('Ini kata pengantar modul aktif.')
        ->assertDontSee('Ini kata pengantar modul lain.');
});

test('quick answers transition to practice with a loading state and keep their progress', function () {
    $question = QuickQuestion::factory()->create([
        'module_id' => $this->module->id,
        'answer' => true,
    ]);
    PracticeForm::factory()->create(['module_id' => $this->module->id]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'quick'])
        ->assertSee('data-learning-player', false)
        ->assertSee('Lagi buka bagian ini', false)
        ->set('quick_answers', [(string) $question->id => '1'])
        ->call('checkQuickAnswers')
        ->assertNotDispatched('module-section-changed')
        ->assertSee('Lagi nyiapin halaman praktik', false)
        ->call('nextSection')
        ->assertSet('section', 'praktik')
        ->assertSet('quick_checked', true)
        ->assertSet('quick_answers.'.$question->id, '1')
        ->assertDispatched('module-section-changed', section: 'praktik');
});

test('choosing the section that is already open does not ask the page to scroll', function () {
    QuickQuestion::factory()->create(['module_id' => $this->module->id]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'quick'])
        ->call('goToSection', 'quick')
        ->assertSet('section', 'quick')
        ->assertNotDispatched('module-section-changed');
});

test('transitions between other sections also ask the page to scroll', function () {
    QuickQuestion::factory()->create(['module_id' => $this->module->id]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'pahami'])
        ->call('nextSection')
        ->assertSet('section', 'quick')
        ->assertDispatched('module-section-changed', section: 'quick');
});

test('a section restored from browser history asks the page to scroll', function () {
    QuickQuestion::factory()->create(['module_id' => $this->module->id]);
    PracticeForm::factory()->create(['module_id' => $this->module->id]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'quick'])
        ->set('section', 'praktik')
        ->assertSet('section', 'praktik')
        ->assertDispatched('module-section-changed', section: 'praktik');
});

test('restoring the same section from browser history does not ask the page to scroll', function () {
    QuickQuestion::factory()->create(['module_id' => $this->module->id]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'quick'])
        ->set('section', 'quick')
        ->assertSet('section', 'quick')
        ->assertNotDispatched('module-section-changed');
});

test('module feedback is stored for the admin report', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'checkpoint'])
        ->set('ratings', ['Materi mudah dipahami', 'AI feedback membantu'])
        ->set('suggestion', 'Tambahkan contoh esai.')
        ->call('saveModuleFeedback')
        ->assertHasNoErrors();

    $feedback = $this->mentee->moduleFeedbacks()->firstWhere('module_id', $this->module->id);

    expect($feedback)->not->toBeNull()
        ->and($feedback->ratings)->toBe(['Materi mudah dipahami', 'AI feedback membantu'])
        ->and($feedback->suggestion)->toBe('Tambahkan contoh esai.');
});

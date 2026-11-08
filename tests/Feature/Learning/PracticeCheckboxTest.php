<?php

use App\Contracts\AiFeedbackServiceInterface;
use App\Data\AiFeedbackData;
use App\Livewire\Learning\ModulePlayer;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $fase = Fase::factory()->create(['fase_order' => 1]);

    $this->module = Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 1]);

    $this->form = PracticeForm::factory()->create([
        'module_id' => $this->module->id,
        'fields' => [
            [
                'name' => 'kegiatan',
                'label' => 'Kegiatan yang pernah kamu ikuti',
                'type' => 'checkbox',
                'required' => true,
                'options' => ['Organisasi', 'Lomba', 'Relawan'],
            ],
        ],
    ]);

    $this->mentee = User::factory()->profiled()->create();

    $this->app->instance(AiFeedbackServiceInterface::class, new class implements AiFeedbackServiceInterface
    {
        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            return new AiFeedbackData(score: 80, strengths: [], improvements: [], feedback: 'Bagus.', next_steps: []);
        }
    });
});

test('a multiple choice field starts as an empty list so options are bound independently', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSet('answers.'.$this->form->id.'.kegiatan', []);
});

test('every multiple choice option renders its own id and matching label', function () {
    $prefix = 'field-answers-'.$this->form->id.'-kegiatan-option-';

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSeeHtml('id="'.$prefix.'0"')
        ->assertSeeHtml('for="'.$prefix.'0"')
        ->assertSeeHtml('id="'.$prefix.'1"')
        ->assertSeeHtml('id="'.$prefix.'2"')
        ->assertSeeHtml('for="'.$prefix.'2"');
});

test('only the ticked options are stored', function (array $ticked) {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->set('answers.'.$this->form->id.'.kegiatan', $ticked)
        ->call('submitPractice', $this->form->id)
        ->assertHasNoErrors();

    $response = $this->mentee->practiceResponses()->firstWhere('practice_form_id', $this->form->id);

    expect($response->answers['kegiatan'])->toBe($ticked);
})->with([
    'satu opsi' => [['Lomba']],
    'beberapa opsi' => [['Organisasi', 'Relawan']],
    'semua opsi' => [['Organisasi', 'Lomba', 'Relawan']],
]);

test('stored multiple choice answers are prefilled as a list', function () {
    $this->mentee->practiceResponses()->create([
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
        'answers' => ['kegiatan' => ['Relawan']],
        'ai_status' => 'completed',
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSet('answers.'.$this->form->id.'.kegiatan', ['Relawan']);
});

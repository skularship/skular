<?php

use App\Enums\AiFeedbackStatus;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create([
        'name' => 'Rani Mentee',
        'phone' => '081234567890',
    ]);
});

test('the cv page shows an empty state before any cv practice is submitted', function () {
    $this->actingAs($this->mentee)
        ->get(route('cv.show'))
        ->assertOk()
        ->assertSee('CV Saya')
        ->assertSee('Belum ada isi CV');
});

test('the cv page lists practice answers flagged for the cv', function () {
    $module = Module::factory()->create(['title' => 'Modul Profil Diri']);

    $form = PracticeForm::factory()->includeInCv(1)->create([
        'module_id' => $module->id,
        'title' => 'Pengalaman Organisasi',
        'fields' => [
            [
                'name' => 'peran',
                'label' => 'Peran / Jabatan',
                'type' => 'text',
                'required' => true,
                'placeholder' => null,
                'help_text' => null,
                'options' => [],
            ],
        ],
    ]);

    $this->mentee->practiceResponses()->create([
        'module_id' => $module->id,
        'practice_form_id' => $form->id,
        'answers' => ['peran' => 'Ketua BEM 2025'],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 85,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->mentee)
        ->get(route('cv.show'))
        ->assertOk()
        ->assertSee('Pengalaman Organisasi')
        ->assertSee('Peran / Jabatan')
        ->assertSee('Ketua BEM 2025')
        ->assertSee('Rani Mentee')
        ->assertSee('081234567890');
});

test('practice forms that are not flagged for the cv stay hidden', function () {
    $module = Module::factory()->create();

    $form = PracticeForm::factory()->create([
        'module_id' => $module->id,
        'title' => 'Latihan Jawaban Wawancara',
    ]);

    $this->mentee->practiceResponses()->create([
        'module_id' => $module->id,
        'practice_form_id' => $form->id,
        'answers' => ['jawaban' => 'Jawaban wawancara yang rahasia.'],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 70,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->mentee)
        ->get(route('cv.show'))
        ->assertOk()
        ->assertDontSee('Latihan Jawaban Wawancara')
        ->assertSee('Belum ada isi CV');
});

test('the cv only shows the answers of the authenticated mentee', function () {
    $module = Module::factory()->create();

    $form = PracticeForm::factory()->includeInCv(1)->create([
        'module_id' => $module->id,
        'title' => 'Pengalaman Organisasi',
        'fields' => [
            ['name' => 'peran', 'label' => 'Peran / Jabatan', 'type' => 'text', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []],
        ],
    ]);

    $other_mentee = User::factory()->profiled()->create(['name' => 'Budi Lain']);

    $this->mentee->practiceResponses()->create([
        'module_id' => $module->id,
        'practice_form_id' => $form->id,
        'answers' => ['peran' => 'Ketua BEM milik Rani'],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 85,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    $other_mentee->practiceResponses()->create([
        'module_id' => $module->id,
        'practice_form_id' => $form->id,
        'answers' => ['peran' => 'Jawaban rahasia Budi'],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 70,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->mentee)
        ->get(route('cv.show'))
        ->assertOk()
        ->assertSee('Ketua BEM milik Rani')
        ->assertDontSee('Jawaban rahasia Budi')
        ->assertDontSee('Budi Lain');
});

test('cv sections follow the order the admin configured', function () {
    $module = Module::factory()->create();

    PracticeForm::factory()->includeInCv(2)->create([
        'module_id' => $module->id,
        'title' => 'Bagian Kedua',
        'fields' => [['name' => 'isi', 'label' => 'Isi kedua', 'type' => 'text', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []]],
    ]);

    $first = PracticeForm::factory()->includeInCv(1)->create([
        'module_id' => $module->id,
        'title' => 'Bagian Pertama',
        'fields' => [['name' => 'isi', 'label' => 'Isi pertama', 'type' => 'text', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []]],
    ]);

    foreach (['Bagian Pertama' => 'Pertama', 'Bagian Kedua' => 'Kedua'] as $title => $value) {
        $form = PracticeForm::query()->where('title', $title)->firstOrFail();

        $this->mentee->practiceResponses()->create([
            'module_id' => $module->id,
            'practice_form_id' => $form->id,
            'answers' => ['isi' => $value],
            'ai_status' => AiFeedbackStatus::Completed,
            'ai_score' => 80,
            'attempts' => 1,
            'submitted_at' => now(),
        ]);
    }

    $this->actingAs($this->mentee)
        ->get(route('cv.show'))
        ->assertOk()
        ->assertSeeInOrder(['Bagian Pertama', 'Bagian Kedua']);

    expect($first->cv_ordering)->toBe(1);
});

test('checkbox answers are rendered as a readable list on the cv', function () {
    $module = Module::factory()->create();

    $form = PracticeForm::factory()->includeInCv(1)->create([
        'module_id' => $module->id,
        'title' => 'Prestasi dan Penghargaan',
        'fields' => [
            ['name' => 'prestasi', 'label' => 'Daftar prestasi', 'type' => 'checkbox', 'required' => false, 'placeholder' => null, 'help_text' => null, 'options' => ['Juara 1', 'Juara 2']],
        ],
    ]);

    $this->mentee->practiceResponses()->create([
        'module_id' => $module->id,
        'practice_form_id' => $form->id,
        'answers' => ['prestasi' => ['Juara 1 Olimpiade', 'Juara 2 Debat']],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 90,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->mentee)
        ->get(route('cv.show'))
        ->assertOk()
        ->assertSee('Daftar prestasi')
        ->assertSee('Juara 1 Olimpiade, Juara 2 Debat');
});

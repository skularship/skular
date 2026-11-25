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

test('file uploads never appear on the cv', function () {
    $module = Module::factory()->create();

    $form = PracticeForm::factory()->includeInCv(1)->create([
        'module_id' => $module->id,
        'title' => 'Sertifikat Prestasi',
        'fields' => [
            ['name' => 'nama', 'label' => 'Nama prestasi', 'type' => 'text', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []],
            ['name' => 'bukti', 'label' => 'Bukti sertifikat', 'type' => 'file', 'required' => false, 'placeholder' => null, 'help_text' => null, 'options' => []],
        ],
    ]);

    $this->mentee->practiceResponses()->create([
        'module_id' => $module->id,
        'practice_form_id' => $form->id,
        'answers' => ['nama' => 'Juara Debat Nasional', 'bukti' => 'practice-uploads/7/rahasia.pdf'],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 80,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->mentee)
        ->get(route('cv.show'))
        ->assertOk()
        ->assertSee('Juara Debat Nasional')
        ->assertDontSee('Bukti sertifikat')
        ->assertDontSee('practice-uploads/7/rahasia.pdf');
});

test('inactive forms and forms of draft modules stay off the cv', function () {
    $draft_module = Module::factory()->draft()->create();
    $published_module = Module::factory()->create();
    $fields = [['name' => 'isi', 'label' => 'Isi', 'type' => 'text', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []]];

    $draft_form = PracticeForm::factory()->includeInCv(1)->create([
        'module_id' => $draft_module->id,
        'title' => 'Bagian Modul Draf',
        'fields' => $fields,
    ]);

    $inactive_form = PracticeForm::factory()->includeInCv(2)->create([
        'module_id' => $published_module->id,
        'title' => 'Bagian Nonaktif',
        'is_active' => false,
        'fields' => $fields,
    ]);

    foreach ([$draft_form, $inactive_form] as $form) {
        $this->mentee->practiceResponses()->create([
            'module_id' => $form->module_id,
            'practice_form_id' => $form->id,
            'answers' => ['isi' => 'Isi tersembunyi '.$form->id],
            'ai_status' => AiFeedbackStatus::Completed,
            'ai_score' => 80,
            'attempts' => 1,
            'submitted_at' => now(),
        ]);
    }

    $this->actingAs($this->mentee)
        ->get(route('cv.show'))
        ->assertOk()
        ->assertDontSee('Bagian Modul Draf')
        ->assertDontSee('Bagian Nonaktif')
        ->assertSee('Belum ada isi CV');
});

test('cv entries follow the current form schema and never show internal answer codes', function () {
    $module = Module::factory()->create();

    $form = PracticeForm::factory()->includeInCv(1)->create([
        'module_id' => $module->id,
        'title' => 'Pengalaman Organisasi',
        'fields' => [
            ['name' => 'deskripsi', 'label' => 'Deskripsi Singkat', 'type' => 'textarea', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []],
            ['name' => 'peran', 'label' => 'Peran / Jabatan', 'type' => 'text', 'required' => true, 'placeholder' => null, 'help_text' => null, 'options' => []],
            ['name' => 'tanpa_label_xyz', 'type' => 'text', 'required' => false],
        ],
    ]);

    $this->mentee->practiceResponses()->create([
        'module_id' => $module->id,
        'practice_form_id' => $form->id,
        'answers' => [
            'peran' => 'Ketua Himpunan',
            'kode_lama_dihapus' => 'Jawaban field yang sudah dihapus',
            'deskripsi' => 'Memimpin 40 anggota.',
            'tanpa_label_xyz' => 'Isi tanpa label',
        ],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 80,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->mentee)
        ->get(route('cv.show'))
        ->assertOk()
        ->assertSeeInOrder(['Deskripsi Singkat', 'Memimpin 40 anggota.', 'Peran / Jabatan', 'Ketua Himpunan'])
        ->assertSee('Isi tanpa label')
        ->assertDontSee('Jawaban field yang sudah dihapus')
        ->assertDontSee('kode_lama_dihapus')
        ->assertDontSee('tanpa_label_xyz');
});

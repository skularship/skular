<?php

use App\Enums\AdminRole;
use App\Enums\MaterialFormat;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Livewire\Learning\ModulePlayer;
use App\Models\Fase;
use App\Models\Module;
use App\Models\ModuleCoreSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $fase = Fase::factory()->create(['fase_order' => 1]);

    $this->module = Module::factory()->create([
        'fase_id' => $fase->id,
        'modul_order' => 1,
        'introduction' => 'Pengantar modul ini.',
        'inti_material' => 'Teks inti materi lama.',
    ]);

    $this->mentee = User::factory()->profiled()->create();
});

test('pahami shows every core column with its subtitle, in order, after the introduction', function () {
    ModuleCoreSection::factory()->for($this->module)->create(['subtitle' => 'Langkah kedua', 'body' => 'Isi kedua.', 'ordering' => 2]);
    ModuleCoreSection::factory()->for($this->module)->create(['subtitle' => 'Langkah pertama', 'body' => 'Isi pertama.', 'ordering' => 1]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'pahami'])
        ->assertSeeInOrder(['Pengantar modul ini.', 'Inti Materi', 'Langkah pertama', 'Isi pertama.', 'Langkah kedua', 'Isi kedua.'])
        ->assertDontSee('Teks inti materi lama.');
});

test('pahami renders the core material as one document with parts, a table of contents and an end marker', function () {
    ModuleCoreSection::factory()->for($this->module)->html()->create([
        'subtitle' => 'Bagian berformat HTML',
        'body' => '<h2>Key Points</h2><p><span class="color" data-color="merah">Penting</span></p>',
        'ordering' => 2,
    ]);
    ModuleCoreSection::factory()->for($this->module)->create(['subtitle' => 'Bagian Markdown', 'body' => 'Isi **tebal**.', 'ordering' => 1]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'pahami'])
        ->assertSeeHtml('data-test="inti-materi"')
        ->assertSeeHtml('data-test="inti-materi-daftar-isi"')
        ->assertSeeHtml('data-test="inti-materi-selesai"')
        ->assertSeeHtml('data-test="pahami-header"')
        ->assertSeeHtml('data-test="next-section"')
        ->assertSeeInOrder(['Bagian 1 dari 2', 'Bagian Markdown', 'Bagian 2 dari 2', 'Bagian berformat HTML', 'Kamu sudah sampai di akhir inti materi.'])
        ->assertSeeHtml('<h6 class="sk-h2">Key Points</h6>')
        ->assertSeeHtml('<span data-color="merah" style="color: #b91c1c">Penting</span>')
        ->assertSeeHtml('<strong>tebal</strong>');
});

test('a single core column has no table of contents or part label', function () {
    ModuleCoreSection::factory()->for($this->module)->create(['subtitle' => 'Satu-satunya', 'body' => 'Isi.', 'ordering' => 1]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'pahami'])
        ->assertSeeHtml('data-test="inti-materi-kolom"')
        ->assertDontSeeHtml('data-test="inti-materi-daftar-isi"')
        ->assertDontSee('Bagian 1 dari 1');
});

test('an html column without visible text is treated as empty', function () {
    ModuleCoreSection::factory()->for($this->module)->html()->create(['subtitle' => 'Kosong', 'body' => '<p></p>', 'ordering' => 1]);
    ModuleCoreSection::factory()->for($this->module)->html()->create(['subtitle' => 'Spasi saja', 'body' => '<p>&nbsp;</p><p> </p>', 'ordering' => 2]);
    ModuleCoreSection::factory()->for($this->module)->html()->create(['subtitle' => 'Berisi', 'body' => '<p>Ada isinya.</p>', 'ordering' => 3]);

    expect($this->module->filledCoreSections()->pluck('subtitle')->all())->toBe(['Berisi']);
});

test('a new column defaults to the markdown format', function () {
    $section = ModuleCoreSection::factory()->for($this->module)->create(['body' => '**Tebal**']);

    expect($section->fresh()->body_format)->toBe(MaterialFormat::Markdown)
        ->and($section->hasBody())->toBeTrue();
});

test('a module without core columns still shows its legacy core text', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'pahami'])
        ->assertOk()
        ->assertSee('Inti Materi')
        ->assertSee('Teks inti materi lama.');
});

test('a module without any core material renders without the core block', function () {
    $this->module->update(['inti_material' => null]);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'pahami'])
        ->assertOk()
        ->assertDontSee('data-test="inti-materi"', false);
});

test('an admin can add, reorder and remove core columns', function () {
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole($role);

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm([
            'coreSections' => [
                ['subtitle' => 'Kenali penilai', 'body' => 'Penilai mencari bukti.'],
                ['subtitle' => 'Susun kerangka', 'body' => 'Mulai dari tesis.'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->module->coreSections()->pluck('subtitle')->all())->toBe(['Kenali penilai', 'Susun kerangka']);

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm([
            'coreSections' => [
                ['subtitle' => 'Susun kerangka', 'body' => 'Mulai dari tesis.'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->module->coreSections()->pluck('subtitle')->all())->toBe(['Susun kerangka']);
});

test('a core column needs a subtitle and a body', function () {
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole($role);

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm([
            'coreSections' => [
                ['subtitle' => '', 'body' => ''],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors();

    expect($this->module->coreSections()->count())->toBe(0);
});

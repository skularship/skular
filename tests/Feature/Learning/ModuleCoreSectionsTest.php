<?php

use App\Enums\AdminRole;
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

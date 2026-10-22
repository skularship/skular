<?php

use App\Enums\ModuleCategory;
use App\Livewire\Learning\Library;
use App\Models\Fase;
use App\Models\Module;
use App\Models\User;
use Livewire\Livewire;

test('the library page lists perpustakaan modules only', function () {
    $mentee = User::factory()->profiled()->create();
    $fase = Fase::factory()->create(['fase_order' => 1]);

    Module::factory()->library()->create([
        'fase_id' => $fase->id,
        'modul_order' => 1,
        'title' => 'Persiapan TOEFL',
    ]);

    Module::factory()->create([
        'fase_id' => $fase->id,
        'modul_order' => 2,
        'category' => ModuleCategory::Inti,
        'title' => 'Modul Inti Tidak Tampil di Perpustakaan',
    ]);

    Livewire::actingAs($mentee)
        ->test(Library::class)
        ->assertSee('Persiapan TOEFL')
        ->assertDontSee('Modul Inti Tidak Tampil di Perpustakaan');
});

test('the library page shows an empty state without modules', function () {
    $mentee = User::factory()->profiled()->create();

    $this->actingAs($mentee)
        ->get(route('library.index'))
        ->assertOk()
        ->assertSee('Belum ada modul perpustakaan');
});

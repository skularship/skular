<?php

use App\Contracts\RichContentRendererInterface;
use App\Enums\AdminRole;
use App\Enums\MaterialFormat;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Models\Fase;
use App\Models\Module;
use App\Models\ModuleCoreSection;
use App\Models\User;
use App\Support\MaterialStyles;
use Database\Seeders\RoleSeeder;
use Filament\Support\Colors\Color;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->curriculum_admin = User::factory()->create(['email_verified_at' => now()]);
    $this->curriculum_admin->assignRole(AdminRole::StaffDivisi->value);

    $this->module = Module::factory()->create(['fase_id' => Fase::factory()->create()->id]);
});

test('a curriculum admin creates and edits core columns as html with whitelisted styles', function () {
    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm([
            'coreSections' => [
                [
                    'subtitle' => 'Kenali penilai',
                    'body' => '<h2>Brief Overview</h2><p><strong>Label:</strong> <span data-font="lora">cerita</span> '
                        .'<span class="color" data-color="teal-brand">penekanan</span> <span data-size="sangat-besar">besar</span></p>',
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $section = $this->module->coreSections()->sole();

    expect($section->body_format)->toBe(MaterialFormat::Html)
        ->and($section->body)
        ->toContain('<h2>Brief Overview</h2>')
        ->toContain('<strong>Label:</strong>')
        ->toContain('data-font="lora"')
        ->toContain('data-color="teal-brand"')
        ->toContain('data-size="sangat-besar"');

    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm([
            'coreSections' => [
                ['subtitle' => 'Kenali penilai', 'body' => '<p>Isi yang <em>diperbarui</em>.</p>'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->module->coreSections()->sole())
        ->body->toContain('<em>diperbarui</em>')
        ->body_format->toBe(MaterialFormat::Html);
});

test('a font or font size outside the whitelist is not stored', function () {
    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm([
            'coreSections' => [
                ['subtitle' => 'Font liar', 'body' => '<p><span data-font="comic-sans">teks</span> <span data-size="raksasa">ukuran</span></p>'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->module->coreSections()->sole()->body)
        ->toContain('teks')
        ->toContain('ukuran')
        ->not->toContain('comic-sans')
        ->not->toContain('raksasa');
});

test('the editor toolbar offers font size, font family and text color', function () {
    ModuleCoreSection::factory()->for($this->module)->html()->create(['subtitle' => 'Kolom', 'body' => '<p>Isi</p>']);

    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->assertSeeHtml('aria-label="Ukuran huruf"')
        ->assertSeeHtml('aria-label="Jenis huruf"')
        ->assertSeeHtml('aria-label="Sangat besar"')
        ->assertSeeHtml('aria-label="Merriweather"');
});

test('opening a legacy markdown column does not touch the database until it is saved', function () {
    $section = ModuleCoreSection::factory()->for($this->module)->create([
        'subtitle' => 'Kolom lama',
        'body' => "## Poin\n\nTeks **tebal** lama.",
    ]);
    $original_updated_at = $section->fresh()->updated_at;

    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->assertOk();

    $fresh = $section->fresh();

    expect($fresh->body)->toBe("## Poin\n\nTeks **tebal** lama.")
        ->and($fresh->body_format)->toBe(MaterialFormat::Markdown)
        ->and($fresh->updated_at->equalTo($original_updated_at))->toBeTrue();

    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    $saved = $section->fresh();

    expect($saved->body_format)->toBe(MaterialFormat::Html)
        ->and($saved->body)
        ->toContain('<h2>Poin</h2>')
        ->toContain('<strong>tebal</strong>')
        ->not->toContain('**');
});

test('the preview renders an html column exactly like the mentee view', function () {
    $body = '<h2>Key Points</h2><p><span data-color="merah">Penting</span><script>alert(1)</script></p>';
    ModuleCoreSection::factory()->for($this->module)->html()->create(['subtitle' => 'Kolom HTML', 'body' => $body]);

    $this->actingAs($this->curriculum_admin)
        ->get(ModuleResource::getUrl('preview', ['record' => $this->module]))
        ->assertOk()
        ->assertSee(app(RichContentRendererInterface::class)->toHtmlFromFormat($body, MaterialFormat::Html, 5), false)
        ->assertSee('<h6 class="sk-h2">Key Points</h6>', false)
        ->assertDontSee('alert(1)', false);
});

test('material color keys never collide with a filament palette name', function () {
    expect(array_intersect(array_keys(MaterialStyles::textColors()), array_keys(Color::all())))->toBe([]);
});

test('livewire accepts rich editor documents nested inside the core column repeater', function () {
    // data.coreSections.record-N.body.content.0.content.0... reaches ~20 levels for lists and tables.
    expect(config('livewire.payload.max_nesting_depth'))->toBeGreaterThanOrEqual(24);
});

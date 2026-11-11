<?php

use App\Contracts\RichContentRendererInterface;
use App\Enums\AdminRole;
use App\Filament\Resources\Modules\ModuleResource;
use App\Livewire\Learning\ModulePlayer;
use App\Models\Fase;
use App\Models\Module;
use App\Models\ModuleCoreSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Render Markdown through the bound renderer.
 */
function richHtml(?string $markdown, int $level = 4): string
{
    return app(RichContentRendererInterface::class)->toHtml($markdown, $level);
}

test('markdown headings get the brand classes and nest under the page outline', function () {
    $html = richHtml("# Judul utama\n\n## Subjudul\n\n### Bagian kecil\n\n#### Lebih dalam");

    expect($html)
        ->toContain('<h4 class="sk-h1">Judul utama</h4>')
        ->toContain('<h5 class="sk-h2">Subjudul</h5>')
        ->toContain('<h6 class="sk-h3">Bagian kecil</h6>')
        ->toContain('<h6 class="sk-h3">Lebih dalam</h6>')
        ->not->toContain('<h1');
});

test('bold text, lists and quotes are rendered', function () {
    $html = richHtml("Ini **poin utama** kamu.\n\n- satu\n- dua\n\n1. pertama\n\n> Kutipan penting");

    expect($html)
        ->toContain('<strong>poin utama</strong>')
        ->toContain('<ul>')
        ->toContain('<ol>')
        ->toContain('<blockquote>');
});

test('raw html and unsafe links from the database are stripped', function () {
    $html = richHtml("Halo <script>alert('x')</script> <img src=x onerror=alert(1)>\n\n[klik](javascript:alert(1)) <a href=\"https://evil.test\">tautan</a>");

    expect($html)
        ->not->toContain('<script')
        ->not->toContain('<img')
        ->not->toContain('onerror')
        ->not->toContain('javascript:')
        ->not->toContain('evil.test');
});

test('external links open safely in a new tab', function () {
    $html = richHtml('[Sumber](https://contoh.org/info)');

    expect($html)
        ->toContain('href="https://contoh.org/info"')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('target="_blank"');
});

test('legacy plain text keeps its paragraphs and line breaks', function () {
    $html = richHtml("Baris pertama.\nBaris kedua.\n\nParagraf baru.");

    expect($html)
        ->toContain("<p>Baris pertama.<br>\nBaris kedua.</p>")
        ->toContain('<p>Paragraf baru.</p>');
});

test('blank material renders nothing', function () {
    expect(richHtml(null))->toBe('')
        ->and(richHtml("   \n "))->toBe('');
});

test('the pahami step renders core material with the brand prose style', function () {
    $fase = Fase::factory()->create(['fase_order' => 1]);
    $module = Module::factory()->create([
        'fase_id' => $fase->id,
        'modul_order' => 1,
        'summary_material' => 'Ringkasan dengan **poin tebal**.',
        'introduction' => 'Pengantar polos tanpa format.',
    ]);
    ModuleCoreSection::factory()->for($module)->create([
        'subtitle' => 'Kolom pertama',
        'body' => "# Judul Hijau\n\nTeks dengan **poin utama**.\n\n<script>alert(1)</script>",
        'ordering' => 1,
    ]);

    Livewire::actingAs(User::factory()->profiled()->create())
        ->test(ModulePlayer::class, ['module' => $module, 'section' => 'pahami'])
        ->assertSeeHtml('class="sk-prose')
        ->assertSeeHtml('<h5 class="sk-h1">Judul Hijau</h5>')
        ->assertSeeHtml('<strong>poin utama</strong>')
        ->assertSeeHtml('<strong>poin tebal</strong>')
        ->assertSee('Pengantar polos tanpa format.')
        ->assertDontSeeHtml('<script>alert(1)</script>');
});

test('the admin preview renders material exactly like the mentee view', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(Role::findOrCreate(AdminRole::SuperAdmin->value, 'web'));

    $fase = Fase::factory()->create();
    $module = Module::factory()->create(['fase_id' => $fase->id]);
    ModuleCoreSection::factory()->for($module)->create([
        'subtitle' => 'Kolom pratinjau',
        'body' => "## Subjudul Pratinjau\n\nIsi dengan **tebal**.",
    ]);

    $this->actingAs($admin)
        ->get(ModuleResource::getUrl('preview', ['record' => $module]))
        ->assertOk()
        ->assertSee(richHtml("## Subjudul Pratinjau\n\nIsi dengan **tebal**."), false)
        ->assertSee('prose', false);
});

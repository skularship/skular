<?php

use App\Contracts\RichContentRendererInterface;
use App\Enums\AdminRole;
use App\Enums\MaterialFormat;
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

/**
 * Render rich editor HTML through the bound renderer.
 */
function richEditorHtml(?string $html, int $level = 4): string
{
    return app(RichContentRendererInterface::class)->toHtmlFromFormat($html, MaterialFormat::Html, $level);
}

test('the markdown format renders exactly like before', function () {
    $markdown = "# Judul\n\n- **Label:** penjelasan\n- dua";

    expect(app(RichContentRendererInterface::class)->toHtmlFromFormat($markdown, 'markdown'))->toBe(richHtml($markdown))
        ->and(app(RichContentRendererInterface::class)->toHtmlFromFormat($markdown, 'tidak-dikenal'))->toBe(richHtml($markdown));
});

test('rich editor html keeps headings, emphasis, lists, quotes and tables', function () {
    $html = richEditorHtml(
        '<h2>Brief Overview</h2><h3>Detail</h3><p><strong>Label:</strong> <em>miring</em> <u>garis</u> <mark>sorot</mark></p>'
        .'<ol><li><p>pertama</p></li></ol><blockquote><p>Catatan penting</p></blockquote><hr>'
        .'<table><tbody><tr><th colspan="1"><p>Kepala</p></th></tr><tr><td><p>Isi</p></td></tr></tbody></table>',
        5,
    );

    expect($html)
        ->toContain('<h6 class="sk-h2">Brief Overview</h6>')
        ->toContain('<h6 class="sk-h3">Detail</h6>')
        ->toContain('<strong>Label:</strong>')
        ->toContain('<em>miring</em>')
        ->toContain('<u>garis</u>')
        ->toContain('<mark>sorot</mark>')
        ->toContain('<ol><li><p>pertama</p></li></ol>')
        ->toContain('<blockquote>')
        ->toContain('<th colspan="1">')
        ->toContain('<td><p>Isi</p></td>');
});

test('whitelisted text colors and fonts survive as generated inline styles', function () {
    $html = richEditorHtml(
        '<p><span class="color" data-color="teal" style="--color: #277c93; --dark-color: #277c93">teal</span> '
        .'<span data-font="lora" style="font-family: \'Lora\', serif">lora</span> '
        .'<span style="color: #B91C1C; font-family: Nunito">tempelan</span></p>',
    );

    expect($html)
        ->toContain('<span data-color="teal" style="color: #277c93">teal</span>')
        ->toContain('data-font="lora" style="font-family: &#039;Lora&#039;, ui-serif, Georgia, serif"')
        ->toContain('data-color="merah" data-font="nunito"')
        ->not->toContain('--color');
});

test('unsafe html, links, styles, colors and fonts are removed', function () {
    $html = richEditorHtml(
        '<p>Halo<script>alert(1)</script><img src="x" onerror="alert(1)"></p>'
        .'<p onclick="alert(1)"><a href="javascript:alert(1)">jahat</a> <a href="data:text/html,x">data</a></p>'
        .'<p><span style="position: fixed; top: 0; background: url(https://evil.test/x.png)">posisi</span></p>'
        .'<p><span data-color="pink" style="color: #ff00ff">warna liar</span> <span style="font-family: Comic Sans MS">font liar</span></p>'
        .'<iframe src="https://evil.test"></iframe><style>body{display:none}</style><div>teks div tetap</div>',
    );

    expect($html)
        ->not->toContain('<script')
        ->not->toContain('alert')
        ->not->toContain('<img')
        ->not->toContain('onerror')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->not->toContain('data:text')
        ->not->toContain('position')
        ->not->toContain('evil.test')
        ->not->toContain('#ff00ff')
        ->not->toContain('pink')
        ->not->toContain('Comic Sans')
        ->not->toContain('<iframe')
        ->not->toContain('display:none')
        ->not->toContain('<div')
        ->toContain('<span>posisi</span>')
        ->toContain('<span>warna liar</span>')
        ->toContain('teks div tetap');
});

test('rich editor links keep only safe schemes and external links open safely', function () {
    $html = richEditorHtml(
        '<p><a href="https://contoh.org/info" target="_self" onclick="x()">Sumber</a> '
        .'<a href="mailto:tim@contoh.org">Surel</a> <a href="/relatif">Relatif</a></p>',
    );

    expect($html)
        ->toContain('<a href="https://contoh.org/info" rel="noopener noreferrer" target="_blank">Sumber</a>')
        ->toContain('<a href="mailto:tim@contoh.org">Surel</a>')
        ->not->toContain('/relatif')
        ->not->toContain('_self')
        ->not->toContain('onclick');
});

test('rich editor html without visible text renders nothing', function () {
    expect(richEditorHtml('<p></p><p>&nbsp;</p>'))->toBe('')
        ->and(richEditorHtml(null))->toBe('');
});

test('markdown converts to editor html without shifting headings', function () {
    $html = app(RichContentRendererInterface::class)->markdownToEditorHtml("# Judul\n\nTeks **tebal**.\n\n<script>x</script>");

    expect($html)
        ->toContain('<h1>Judul</h1>')
        ->toContain('<strong>tebal</strong>')
        ->not->toContain('<script');
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

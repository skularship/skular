<?php

use App\Livewire\Faq\Index;
use App\Models\Faq;
use App\Models\FaqCategory;
use Livewire\Livewire;

/**
 * Create a published FAQ in a fresh active category.
 */
function faqPublicEntry(string $question, array $attributes = []): Faq
{
    return Faq::factory()->published()->create(array_merge([
        'question' => $question,
        'answer' => '<p>Jawaban untuk '.$question.'</p>',
    ], $attributes));
}

test('the public FAQ page only shows published FAQs from active categories', function () {
    $category = FaqCategory::factory()->create(['name' => 'Pengetahuan Dasar']);
    $inactive = FaqCategory::factory()->inactive()->create(['name' => 'Kategori Pensiun']);

    $visible = faqPublicEntry('Pertanyaan yang boleh dibaca', ['faq_category_id' => $category->id]);
    $draft = Faq::factory()->draft()->create([
        'faq_category_id' => $category->id,
        'question' => 'Draf yang tidak boleh bocor',
    ]);
    $hiddenCategory = faqPublicEntry('FAQ dari kategori nonaktif', ['faq_category_id' => $inactive->id]);
    $scheduled = faqPublicEntry('FAQ yang belum waktunya terbit', [
        'faq_category_id' => $category->id,
        'published_at' => now()->addDay(),
    ]);

    $this->get(route('faq.index'))
        ->assertOk()
        ->assertSee('FAQ - Skular')
        ->assertSee('<meta name="description"', false)
        ->assertSee('Pertanyaan yang Sering Diajukan')
        ->assertSee($visible->question)
        ->assertDontSee($draft->question)
        ->assertDontSee($hiddenCategory->question)
        ->assertDontSee($scheduled->question)
        ->assertDontSee('Kategori Pensiun');
});

test('FAQ search matches both questions and answers without leaking drafts', function () {
    faqPublicEntry('Bagaimana cara mengatur jadwal belajar?', [
        'answer' => '<p>Gunakan kalender mingguan dan evaluasi rutin.</p>',
    ]);
    faqPublicEntry('Apa itu Skular?');

    Faq::factory()->draft()->create([
        'question' => 'Draf tentang kalender rahasia',
        'answer' => '<p>Kalender ini belum boleh dibaca.</p>',
    ]);

    Livewire::test(Index::class)
        ->set('search', 'kalender')
        ->assertSee('Bagaimana cara mengatur jadwal belajar?')
        ->assertDontSee('Apa itu Skular?')
        ->assertDontSee('Draf tentang kalender rahasia');

    Livewire::test(Index::class)
        ->set('search', 'Skular')
        ->assertSee('Apa itu Skular?')
        ->assertDontSee('Bagaimana cara mengatur jadwal belajar?');
});

test('the category filter combines with the search term', function () {
    $administration = FaqCategory::factory()->create(['name' => 'Administrasi']);
    $essay = FaqCategory::factory()->create(['name' => 'Esai']);

    $adminFaq = faqPublicEntry('Surat rekomendasi wajib?', ['faq_category_id' => $administration->id]);
    $essayFaq = faqPublicEntry('Bagaimana struktur esai?', ['faq_category_id' => $essay->id]);

    Livewire::test(Index::class)
        ->call('selectCategory', $administration->slug)
        ->assertSee($adminFaq->question)
        ->assertDontSee($essayFaq->question)
        ->set('search', 'struktur')
        ->assertDontSee($adminFaq->question)
        ->assertDontSee($essayFaq->question)
        ->assertSee('Tidak ada FAQ yang cocok');

    Livewire::test(Index::class)
        ->call('selectCategory', $essay->slug)
        ->assertSee($essayFaq->question)
        ->assertDontSee($adminFaq->question)
        ->call('clearFilters')
        ->assertSee($adminFaq->question)
        ->assertSee($essayFaq->question);
});

test('the public FAQ page paginates ten questions at a time', function () {
    $category = FaqCategory::factory()->create();

    faqPublicEntry('Pertanyaan pembuka', [
        'faq_category_id' => $category->id,
        'sort_order' => 0,
    ]);

    collect(range(1, 11))->each(fn (int $index) => faqPublicEntry(
        'Pertanyaan halaman '.$index,
        ['faq_category_id' => $category->id, 'sort_order' => $index],
    ));

    $component = Livewire::test(Index::class);

    $page = $component->viewData('faqs');

    expect($page->total())->toBe(12)
        ->and($page->count())->toBe(10)
        ->and($page->hasMorePages())->toBeTrue();

    $component->call('gotoPage', 2)
        ->assertSee('Pertanyaan halaman 10')
        ->assertSee('Pertanyaan halaman 11')
        ->assertDontSee('Pertanyaan pembuka');
});

test('the public FAQ page shows a clear empty state', function () {
    $this->get(route('faq.index'))
        ->assertOk()
        ->assertSee('Belum ada FAQ publik')
        ->assertSee('Kembali ke beranda');
});

test('an unknown category URL falls back to the complete published list', function () {
    faqPublicEntry('Pertanyaan tetap terlihat');

    Livewire::test(Index::class)
        ->set('category', 'kategori-yang-tidak-ada')
        ->assertSee('Pertanyaan tetap terlihat');
});

test('rich answers are sanitized before reaching the public page', function () {
    faqPublicEntry('Apakah jawaban aman?', [
        'answer' => '<p>Jawaban <strong>aman</strong>.</p><script>alert("xss")</script><p><a href="javascript:alert(1)">tautan</a></p>',
    ]);

    $response = $this->get(route('faq.index'))->assertOk();

    $response->assertSee('Jawaban')
        ->assertSee('<strong>aman</strong>', false)
        ->assertDontSee('<script>alert', false)
        ->assertDontSee('javascript:alert', false);
});

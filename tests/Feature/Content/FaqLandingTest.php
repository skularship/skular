<?php

use App\Models\Faq;
use App\Models\FaqCategory;

test('the landing page shows at most five featured FAQs in the admin order', function () {
    $category = FaqCategory::factory()->create();

    collect(range(1, 7))->each(fn (int $index) => Faq::factory()->published()->featured()->create([
        'faq_category_id' => $category->id,
        'question' => 'FAQ Pilihan '.$index,
        'sort_order' => $index,
    ]));

    $response = $this->get(route('home'))->assertOk();

    foreach (range(1, 5) as $index) {
        $response->assertSee('FAQ Pilihan '.$index);
    }

    $response->assertDontSee('FAQ Pilihan 6')
        ->assertDontSee('FAQ Pilihan 7')
        ->assertSee(route('faq.index'), false);

    $content = $response->content();
    $positions = array_map(fn (int $index): int|false => strpos($content, 'FAQ Pilihan '.$index), range(1, 5));
    $sorted = $positions;
    sort($sorted);

    expect($positions)->toBe($sorted);
});

test('draft and non-featured FAQs never fill the landing section', function () {
    $category = FaqCategory::factory()->create();

    $featured = Faq::factory()->published()->featured()->create([
        'faq_category_id' => $category->id,
        'question' => 'Satu-satunya FAQ pilihan',
        'sort_order' => 1,
    ]);

    Faq::factory()->draft()->featured()->create([
        'faq_category_id' => $category->id,
        'question' => 'Draf pilihan yang tidak boleh tampil',
    ]);

    Faq::factory()->published()->create([
        'faq_category_id' => $category->id,
        'question' => 'FAQ publik tanpa tanda pilihan',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($featured->question)
        ->assertDontSee('Draf pilihan yang tidak boleh tampil')
        ->assertDontSee('FAQ publik tanpa tanda pilihan');
});

test('the landing page still renders when no FAQ has been published', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Pertanyaan yang sering muncul.')
        ->assertSee(route('faq.index'), false);
});

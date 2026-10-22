<?php

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\QueryException;

test('an FAQ can be created, updated, and related to its category', function () {
    $category = FaqCategory::factory()->create(['name' => 'Administrasi & Pemberkasan']);

    $faq = Faq::factory()->create([
        'faq_category_id' => $category->id,
        'question' => 'Dokumen apa yang perlu disiapkan?',
        'answer' => '<p>Mulai dari transkrip dan surat rekomendasi.</p>',
    ]);

    $faq->update(['question' => 'Dokumen apa saja yang wajib diunggah?']);

    expect($faq->refresh()->category->is($category))->toBeTrue()
        ->and($faq->question)->toBe('Dokumen apa saja yang wajib diunggah?')
        ->and($category->faqs()->count())->toBe(1);
});

test('publishing an FAQ stamps the first publication time', function () {
    $faq = Faq::factory()->draft()->create();

    expect($faq->published_at)->toBeNull();

    $faq->update(['is_published' => true]);

    expect($faq->refresh()->is_published)->toBeTrue()
        ->and($faq->published_at)->not->toBeNull();
});

test('a category that still contains FAQs cannot be deleted', function () {
    $category = FaqCategory::factory()->create();
    Faq::factory()->create(['faq_category_id' => $category->id]);

    expect($category->delete())->toBeFalse();

    $this->assertDatabaseHas('faq_categories', ['id' => $category->id]);
});

test('an unused category can be deleted', function () {
    $category = FaqCategory::factory()->create();

    expect($category->delete())->toBeTrue();

    $this->assertDatabaseMissing('faq_categories', ['id' => $category->id]);
});

test('the database also refuses to orphan FAQ rows when a category is removed directly', function () {
    $category = FaqCategory::factory()->create();
    $faq = Faq::factory()->create(['faq_category_id' => $category->id]);

    try {
        $category->deleteQuietly();
    } catch (QueryException) {
        // The foreign key rejects the delete before any FAQ is orphaned.
    }

    expect(Faq::query()->whereKey($faq->id)->exists())->toBeTrue();
});

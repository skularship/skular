<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            // A category that still holds FAQs cannot be deleted; the admin
            // panel also blocks the action with a clear message.
            $table->foreignId('faq_category_id')->constrained()->restrictOnDelete();
            $table->text('question');
            $table->longText('answer');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Landing page: published + featured, then deterministic order.
            $table->index(
                ['is_published', 'is_featured', 'sort_order'],
                'faqs_featured_lookup_index',
            );

            // Full FAQ page: published list and category-filtered list.
            $table->index(
                ['is_published', 'sort_order'],
                'faqs_public_order_index',
            );
            $table->index(
                ['faq_category_id', 'is_published', 'sort_order'],
                'faqs_category_lookup_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One Try Out package (e.g. TO1-AC). The exam type keeps the schema generic
     * (IELTS in v1, SAT later). New table only: no existing data is touched.
     */
    public function up(): void
    {
        Schema::create('tryouts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('exam_type', 16)->default('ielts');
            $table->string('variant', 16)->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('status', 16)->default('draft');
            $table->boolean('is_trial')->default(false);
            $table->boolean('requires_pro')->default(false);
            $table->foreignId('theory_module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->string('materials_url', 500)->nullable();
            $table->string('materials_label')->nullable();
            $table->string('theory_pdf_path')->nullable();
            $table->string('theory_pdf_name')->nullable();
            $table->timestamp('theory_pdf_updated_at')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'ordering']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tryouts');
    }
};

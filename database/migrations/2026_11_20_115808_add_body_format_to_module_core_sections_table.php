<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Additive: records the format of each Inti Materi body. Existing rows
     * keep their Markdown text and get 'markdown'; the admin rich editor
     * saves 'html'. No data is converted here.
     */
    public function up(): void
    {
        Schema::table('module_core_sections', function (Blueprint $table) {
            $table->string('body_format', 16)->default('markdown')->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_core_sections', function (Blueprint $table) {
            $table->dropColumn('body_format');
        });
    }
};

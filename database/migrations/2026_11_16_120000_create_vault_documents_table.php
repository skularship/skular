<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the table of documents a Pro mentee keeps in the vault.
     */
    public function up(): void
    {
        Schema::create('vault_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedInteger('size_kb');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Drop the vault documents table.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_documents');
    }
};

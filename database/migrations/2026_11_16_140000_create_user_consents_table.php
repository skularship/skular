<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the consent log: one row per legal document version a mentee accepted.
     *
     * No IP address is stored. Rows go with the account (cascade) and are
     * never updated; a new version adds a new row.
     */
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('document', 20);
            $table->string('version', 20);
            $table->string('source', 20);
            $table->boolean('age_confirmed')->nullable();
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->index(['user_id', 'document', 'version']);
        });
    }

    /**
     * Drop the consent log.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};

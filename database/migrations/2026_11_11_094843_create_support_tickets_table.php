<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Help requests and bug reports sent from the "Bantuan" page. Tickets
     * outlive the account that sent them: user_id and handled_by are nulled
     * instead of cascading. The optional screenshot lives on the private
     * `local` disk.
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 32);
            $table->string('subject', 150);
            $table->text('description');
            $table->string('page_url', 2048)->nullable();
            $table->string('screenshot_path')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('status', 16)->default('new');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};

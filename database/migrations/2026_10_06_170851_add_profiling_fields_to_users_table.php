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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar_url')->nullable();
            $table->string('level')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->boolean('has_sktm')->nullable();
            $table->string('desired_level')->nullable();
            $table->string('desired_location')->nullable();
            $table->foreignId('cluster_id')->nullable()->index();
            $table->string('phone', 20)->nullable();

            // Extension point for the (not yet implemented) pricing feature.
            $table->string('subscription')->nullable();

            $table->timestamp('profile_completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'avatar_url',
                'level',
                'age',
                'gpa',
                'has_sktm',
                'desired_level',
                'desired_location',
                'cluster_id',
                'phone',
                'subscription',
                'profile_completed_at',
            ]);
        });
    }
};

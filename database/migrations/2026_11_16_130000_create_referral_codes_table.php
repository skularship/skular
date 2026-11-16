<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the referral codes (one per mentee) and record the promo on orders.
     */
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('promo_code')->nullable()->after('amount');
            $table->foreignId('referrer_user_id')->nullable()->after('promo_code')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('discount_amount')->default(0)->after('referrer_user_id');
        });
    }

    /**
     * Remove the referral columns and table.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referrer_user_id');
            $table->dropColumn(['promo_code', 'discount_amount']);
        });

        Schema::dropIfExists('referral_codes');
    }
};

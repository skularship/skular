<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the Premium products table and seed the two Pro plans.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('price_normal');
            $table->unsignedInteger('price_launching')->nullable();
            $table->unsignedSmallInteger('duration_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('products')->insert([
            [
                'code' => 'pro_1bln',
                'name' => 'Pro 1 bulan',
                'price_normal' => 50000,
                'price_launching' => null,
                'duration_days' => 30,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'pro_12bln',
                'name' => 'Pro 12 bulan',
                'price_normal' => 149000,
                'price_launching' => 74500,
                'duration_days' => 365,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Drop the products table.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

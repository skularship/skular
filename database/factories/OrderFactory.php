<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state: a pending Pro 12 bulan order.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => 'SKL-'.Str::upper(Str::random(12)),
            'user_id' => User::factory(),
            'product_id' => fn () => Product::query()->where('code', 'pro_12bln')->value('id'),
            'amount' => 149000,
            'status' => OrderStatus::Pending,
            'snap_token' => 'snap-token-test',
        ];
    }

    /**
     * Indicate the order is already paid.
     */
    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}

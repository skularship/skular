<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A Premium plan that can be bought: Pro 1 bulan or Pro 12 bulan.
 *
 * Prices are whole rupiah. The launching price only applies while
 * config('pricing.launching_until') is in the future; see PricingService.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $price_normal
 * @property int|null $price_launching
 * @property int $duration_days
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Order> $orders
 */
#[Fillable(['code', 'name', 'price_normal', 'price_launching', 'duration_days', 'is_active'])]
class Product extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_normal' => 'integer',
            'price_launching' => 'integer',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the number of months this plan covers (30 days = 1 month, 365 days = 12 months).
     */
    public function months(): int
    {
        return max(1, (int) round($this->duration_days / 30));
    }

    /**
     * Get the orders placed for this plan.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One purchase attempt of a Premium plan. The amount is fixed at creation so a
 * later price change never alters a pending order; Pro is activated only when
 * the verified Midtrans notification marks the order as paid.
 *
 * @property int $id
 * @property string $order_id Unique id sent to Midtrans.
 * @property int $user_id
 * @property int $product_id
 * @property int $amount Whole rupiah.
 * @property string|null $promo_code
 * @property int|null $referrer_user_id Owner of the referral code used, for future rewards.
 * @property int $discount_amount Rupiah taken off by a referral code.
 * @property OrderStatus $status
 * @property string|null $snap_token
 * @property string|null $midtrans_transaction_id
 * @property string|null $payment_type
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Product $product
 */
#[Fillable([
    'order_id',
    'user_id',
    'product_id',
    'amount',
    'promo_code',
    'referrer_user_id',
    'discount_amount',
    'status',
    'snap_token',
    'midtrans_transaction_id',
    'payment_type',
    'paid_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the route key so status URLs use the public order id, not the row id.
     */
    public function getRouteKeyName(): string
    {
        return 'order_id';
    }

    /**
     * Get the mentee who placed the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the purchased plan.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

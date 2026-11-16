<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * What one Premium plan costs right now, ready to show: price, per-month price,
 * the struck-through reference price and the saving, all computed in one place.
 */
final class PriceQuote
{
    /**
     * @param  string  $code  Product code (pro_1bln, pro_12bln).
     * @param  string  $name  Plan name.
     * @param  int  $months  Months of access (1 or 12).
     * @param  int  $amount  Total to pay in rupiah.
     * @param  int  $monthly  Total divided by months, rounded to rupiah.
     * @param  int|null  $strike_total  Reference total shown struck through, null when there is none.
     * @param  int|null  $strike_monthly  Reference price per month, null when there is none.
     * @param  int|null  $saving_percent  Whole-percent saving against the reference, null when there is none.
     * @param  bool  $is_launching  Whether the launching promo price applies.
     * @param  CarbonImmutable|null  $launching_until  End of the promo, only set while it applies.
     */
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly int $months,
        public readonly int $amount,
        public readonly int $monthly,
        public readonly ?int $strike_total,
        public readonly ?int $strike_monthly,
        public readonly ?int $saving_percent,
        public readonly bool $is_launching,
        public readonly ?CarbonImmutable $launching_until,
    ) {}
}

<?php

namespace App\Contracts;

use App\Data\PriceQuote;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Single source for Premium prices. Landing, /premium, dashboard and checkout
 * all read from here so a price can never differ between screens.
 */
interface PricingServiceInterface
{
    /**
     * Get the quotes of every active plan, keyed by product code, shortest plan first.
     *
     * @return Collection<string, PriceQuote>
     */
    public function quotes(): Collection;

    /**
     * Get the quote of one plan, or null when it does not exist or is not for sale.
     */
    public function quote(string $code): ?PriceQuote;

    /**
     * Get the active product for a code, or null.
     */
    public function product(string $code): ?Product;
}

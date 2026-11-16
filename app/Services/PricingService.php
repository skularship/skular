<?php

namespace App\Services;

use App\Contracts\PricingServiceInterface;
use App\Data\PriceQuote;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Computes Premium prices from the products table and config('pricing').
 *
 * While the launching promo runs, the struck-through price is the normal price
 * (Rp149.000 for 12 months). After it ends, the reference is the 1-month price
 * times the months (Rp600.000), so every struck-through price can be proven.
 */
class PricingService implements PricingServiceInterface
{
    /**
     * Get the quotes of every active plan, keyed by product code, shortest plan first.
     *
     * @return Collection<string, PriceQuote>
     */
    public function quotes(): Collection
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('duration_days')
            ->get();

        $monthly_base = $products->firstWhere('duration_days', 30);
        $until = $this->launchingUntil();

        return $products->mapWithKeys(
            fn (Product $product): array => [$product->code => $this->build($product, $monthly_base, $until)],
        );
    }

    /**
     * Get the quote of one plan, or null when it does not exist or is not for sale.
     */
    public function quote(string $code): ?PriceQuote
    {
        return $this->quotes()->get($code);
    }

    /**
     * Get the active product for a code, or null.
     */
    public function product(string $code): ?Product
    {
        return Product::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get the end of the launching promo when it is still running.
     */
    private function launchingUntil(): ?CarbonImmutable
    {
        $raw = config('pricing.launching_until');

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            $until = CarbonImmutable::parse($raw);
        } catch (Throwable) {
            return null;
        }

        return $until->isFuture() ? $until : null;
    }

    /**
     * Build the quote of one product.
     */
    private function build(Product $product, ?Product $monthly_base, ?CarbonImmutable $until): PriceQuote
    {
        $is_launching = $product->price_launching !== null && $until instanceof CarbonImmutable;
        $amount = $is_launching ? (int) $product->price_launching : $product->price_normal;
        $months = $product->months();

        $reference = match (true) {
            $is_launching => $product->price_normal,
            $months > 1 && $monthly_base instanceof Product => $monthly_base->price_normal * $months,
            default => null,
        };

        $has_strike = $reference !== null && $reference > $amount;

        return new PriceQuote(
            code: $product->code,
            name: $product->name,
            months: $months,
            amount: $amount,
            monthly: (int) round($amount / $months),
            strike_total: $has_strike ? $reference : null,
            strike_monthly: $has_strike ? (int) round($reference / $months) : null,
            saving_percent: $has_strike ? (int) round((1 - $amount / $reference) * 100) : null,
            is_launching: $is_launching,
            launching_until: $is_launching ? $until : null,
        );
    }
}

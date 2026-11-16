<?php

namespace App\Services;

use App\Contracts\ReferralServiceInterface;
use App\Data\PriceQuote;
use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * Creates referral codes and validates them at checkout.
 *
 * Rules: only the 12 month plan while launching pricing runs, 10% of the launching
 * price, never your own code, and an account can use a referral code once.
 */
class ReferralService implements ReferralServiceInterface
{
    /**
     * Discount in percent, taken from the price after the launching discount.
     */
    public const PERCENT = 10;

    /**
     * Plan that accepts a referral code.
     */
    public const ELIGIBLE_PLAN = 'pro_12bln';

    /**
     * Get the mentee's own code, creating it the first time.
     */
    public function codeFor(User $user): ReferralCode
    {
        $existing = ReferralCode::query()->where('user_id', $user->id)->first();

        if ($existing instanceof ReferralCode) {
            return $existing;
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return ReferralCode::query()->create(['user_id' => $user->id, 'code' => $this->generate()]);
            } catch (UniqueConstraintViolationException) {
                $existing = ReferralCode::query()->where('user_id', $user->id)->first();

                if ($existing instanceof ReferralCode) {
                    return $existing;
                }
            }
        }

        throw PaymentException::promoInvalid(__('Kode referral belum bisa dibuat. Coba lagi.'));
    }

    /**
     * Check a code typed by a buyer and work out the discount for the given price.
     *
     * @return array{code: ReferralCode, discount: int}
     *
     * @throws PaymentException When the code cannot be used.
     */
    public function apply(User $buyer, string $code, PriceQuote $quote): array
    {
        $referral = ReferralCode::query()->where('code', Str::upper(trim($code)))->first();

        if (! $referral instanceof ReferralCode) {
            throw PaymentException::promoInvalid(__('Kode referral tidak ditemukan.'));
        }

        if ($referral->user_id === $buyer->id) {
            throw PaymentException::promoInvalid(__('Kamu tidak bisa memakai kodemu sendiri.'));
        }

        if ($quote->code !== self::ELIGIBLE_PLAN || ! $quote->is_launching) {
            throw PaymentException::promoInvalid(__('Kode referral hanya untuk paket 12 bulan selama harga launching.'));
        }

        $already_used = Order::query()
            ->where('user_id', $buyer->id)
            ->whereNotNull('referrer_user_id')
            ->where('status', OrderStatus::Paid)
            ->exists();

        if ($already_used) {
            throw PaymentException::promoInvalid(__('Kamu sudah pernah memakai kode referral.'));
        }

        return ['code' => $referral, 'discount' => (int) round($quote->amount * self::PERCENT / 100)];
    }

    /**
     * Make a short code that avoids look-alike characters.
     */
    private function generate(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < 7; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }
}

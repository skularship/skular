<?php

namespace App\Services;

use App\Contracts\IqMentorQuotaServiceInterface;
use App\Models\IqMentorUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Tes IQ mentor quota per account per day (Asia/Jakarta), same pattern as
 * MentorQuotaService: reserved under a row lock before the model is called
 * and given back when the call fails, so parallel clicks never pass the limit.
 */
class IqMentorQuotaService implements IqMentorQuotaServiceInterface
{
    /**
     * Fallback when the configured limit is missing or invalid.
     */
    public const DEFAULT_LIMIT = 10;

    /**
     * Configured number of model questions per day.
     */
    public function limit(): int
    {
        $limit = config('iq.mentor.daily_limit', self::DEFAULT_LIMIT);

        return is_numeric($limit) ? max(0, (int) $limit) : self::DEFAULT_LIMIT;
    }

    /**
     * Questions the mentee may still send today.
     */
    public function remaining(User $user): int
    {
        $used = IqMentorUsage::query()
            ->where('user_id', $user->id)
            ->whereDate('usage_date', $this->today())
            ->value('used');

        return max(0, $this->limit() - (int) $used);
    }

    /**
     * Reserve one question under a row lock before calling the model.
     */
    public function reserve(User $user): bool
    {
        $limit = $this->limit();
        $today = $this->today();

        return DB::transaction(function () use ($user, $limit, $today): bool {
            $usage = IqMentorUsage::query()
                ->where('user_id', $user->id)
                ->whereDate('usage_date', $today)
                ->first()
                ?? IqMentorUsage::query()->create(['user_id' => $user->id, 'usage_date' => $today, 'used' => 0]);

            $usage = IqMentorUsage::query()->whereKey($usage->getKey())->lockForUpdate()->firstOrFail();

            if ($usage->used >= $limit) {
                return false;
            }

            $usage->increment('used');

            return true;
        });
    }

    /**
     * Give a reserved question back after the model call failed.
     */
    public function release(User $user): void
    {
        IqMentorUsage::query()
            ->where('user_id', $user->id)
            ->whereDate('usage_date', $this->today())
            ->where('used', '>', 0)
            ->decrement('used');
    }

    /**
     * Today's date in the quota timezone (Y-m-d).
     */
    private function today(): string
    {
        return now()->setTimezone((string) config('iq.mentor.timezone', 'Asia/Jakarta'))->toDateString();
    }
}

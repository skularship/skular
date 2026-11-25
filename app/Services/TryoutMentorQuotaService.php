<?php

namespace App\Services;

use App\Contracts\TryoutMentorQuotaServiceInterface;
use App\Models\TryoutAttempt;
use App\Models\TryoutMentorUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Try Out mentor quota per mentee per attempt (copy of the MentorQuotaService
 * pattern): reserved under a row lock before the model is called, given back
 * when the call fails, so parallel clicks never pass the limit.
 */
class TryoutMentorQuotaService implements TryoutMentorQuotaServiceInterface
{
    /**
     * Fallback when the configured limit is missing or invalid.
     */
    public const DEFAULT_LIMIT = 10;

    /**
     * Configured number of model questions per attempt.
     */
    public function limit(): int
    {
        $limit = config('tryout.mentor.limit', self::DEFAULT_LIMIT);

        return is_numeric($limit) ? max(0, (int) $limit) : self::DEFAULT_LIMIT;
    }

    /**
     * Questions the mentee may still send for the attempt.
     */
    public function remaining(User $user, TryoutAttempt $attempt): int
    {
        $used = TryoutMentorUsage::query()
            ->where('user_id', $user->id)
            ->where('tryout_attempt_id', $attempt->id)
            ->value('used');

        return max(0, $this->limit() - (int) $used);
    }

    /**
     * Reserve one question under a row lock before calling the model.
     */
    public function reserve(User $user, TryoutAttempt $attempt): bool
    {
        $limit = $this->limit();

        return DB::transaction(function () use ($user, $attempt, $limit): bool {
            $usage = TryoutMentorUsage::query()->firstOrCreate(
                ['user_id' => $user->id, 'tryout_attempt_id' => $attempt->id],
                ['used' => 0],
            );

            $usage = TryoutMentorUsage::query()->whereKey($usage->getKey())->lockForUpdate()->firstOrFail();

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
    public function release(User $user, TryoutAttempt $attempt): void
    {
        TryoutMentorUsage::query()
            ->where('user_id', $user->id)
            ->where('tryout_attempt_id', $attempt->id)
            ->where('used', '>', 0)
            ->decrement('used');
    }
}

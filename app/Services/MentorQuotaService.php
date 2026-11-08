<?php

namespace App\Services;

use App\Contracts\MentorQuotaServiceInterface;
use App\Models\Module;
use App\Models\ModuleMentorUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Stores the AI Mentor quota per mentee per module in the database.
 *
 * A question is reserved under a row lock before the model is called and
 * released again when the call fails, so parallel clicks can never exceed the
 * limit and a provider error never costs the mentee a question.
 */
class MentorQuotaService implements MentorQuotaServiceInterface
{
    /**
     * Fallback when the configured limit is missing or invalid.
     */
    public const DEFAULT_LIMIT = 5;

    /**
     * Get the configured number of model questions per mentee per module.
     *
     * @return int config('ai.module_chat.limit'), falling back to DEFAULT_LIMIT when invalid.
     */
    public function limit(): int
    {
        $limit = config('ai.module_chat.limit', self::DEFAULT_LIMIT);

        return is_numeric($limit) ? max(0, (int) $limit) : self::DEFAULT_LIMIT;
    }

    /**
     * Get how many model questions the mentee may still send for the module.
     *
     * @param  User  $user  The mentee.
     * @param  Module  $module  The module of the chat.
     * @return int Remaining questions, never below zero.
     */
    public function remaining(User $user, Module $module): int
    {
        $used = ModuleMentorUsage::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->value('used');

        return max(0, $this->limit() - (int) $used);
    }

    /**
     * Reserve one question under a row lock before calling the model.
     *
     * @param  User  $user  The mentee.
     * @param  Module  $module  The module of the chat.
     * @return bool False when the quota is already used up.
     */
    public function reserve(User $user, Module $module): bool
    {
        $limit = $this->limit();

        return DB::transaction(function () use ($user, $module, $limit): bool {
            // firstOrCreate + the unique (user_id, module_id) index makes
            // concurrent first questions converge on one row.
            $usage = ModuleMentorUsage::query()->firstOrCreate(
                ['user_id' => $user->id, 'module_id' => $module->id],
                ['used' => 0],
            );

            $usage = ModuleMentorUsage::query()->whereKey($usage->getKey())->lockForUpdate()->firstOrFail();

            if ($usage->used >= $limit) {
                return false;
            }

            $usage->increment('used');

            return true;
        });
    }

    /**
     * Give a reserved question back after the model call failed.
     *
     * @param  User  $user  The mentee.
     * @param  Module  $module  The module of the chat.
     */
    public function release(User $user, Module $module): void
    {
        ModuleMentorUsage::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('used', '>', 0)
            ->decrement('used');
    }
}

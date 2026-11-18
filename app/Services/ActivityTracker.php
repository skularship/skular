<?php

namespace App\Services;

use App\Contracts\ActivityTrackerInterface;
use App\Data\FunnelStepData;
use App\Enums\ActivityName;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes the behaviour log synchronously (one insert per action) and builds
 * the admin funnel from it. A failing insert is logged and swallowed.
 */
class ActivityTracker implements ActivityTrackerInterface
{
    /**
     * Record one action. Never throws: tracking must not break the main flow.
     *
     * @param  ActivityName  $name  The tracked action.
     * @param  array<string, mixed>  $properties  Small context, e.g. ['module_id' => 3].
     * @param  User|null  $user  The mentee; defaults to the signed-in user (null for guests).
     */
    public function track(ActivityName $name, array $properties = [], ?User $user = null): void
    {
        try {
            $user ??= Auth::user() instanceof User ? Auth::user() : null;

            ActivityLog::query()->create([
                'user_id' => $user?->id,
                'session_key' => $this->sessionKey(),
                'name' => $name,
                'properties' => $properties === [] ? null : $properties,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Activity tracking failed', [
                'name' => $name->value,
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * Count the funnel of mentees who registered in the last $days days.
     *
     * @param  int  $days  Size of the registration window.
     * @return array<int, FunnelStepData>
     */
    public function funnel(int $days): array
    {
        $cohort = ActivityLog::query()
            ->select('user_id')
            ->where('name', ActivityName::Registered)
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subDays($days));

        /** @var array<string, int|string> $counts */
        $counts = ActivityLog::query()
            ->whereIn('user_id', $cohort)
            ->whereIn('name', array_map(fn (ActivityName $step): string => $step->value, ActivityName::funnel()))
            ->groupBy('name')
            ->toBase()
            ->selectRaw('name, count(distinct user_id) as total')
            ->pluck('total', 'name')
            ->all();

        $steps = [];
        $previous = null;

        foreach (ActivityName::funnel() as $step) {
            $count = (int) ($counts[$step->value] ?? 0);

            $steps[] = new FunnelStepData(
                step: $step,
                count: $count,
                continued_percentage: $previous === null || $previous === 0 ? null : (int) round($count / $previous * 100),
            );

            $previous = $count;
        }

        return $steps;
    }

    /**
     * Hash the session id so anonymous visits can be grouped without storing the session token itself.
     */
    private function sessionKey(): ?string
    {
        if (! app()->bound('session') || ! session()->isStarted()) {
            return null;
        }

        return hash('sha256', session()->getId());
    }
}

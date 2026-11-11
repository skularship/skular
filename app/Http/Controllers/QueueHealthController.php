<?php

namespace App\Http\Controllers;

use App\Models\UserPracticeResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Uptime probe for the queue worker that runs the AI practice evaluation.
 *
 * Kept apart from /health on purpose: a dead worker must alert the team, but
 * must not make a load balancer pull healthy web servers. Returns 503 when a
 * job of the database queue has waited longer than the stall window, which
 * means no worker is taking jobs. Never returns payloads or internals.
 */
class QueueHealthController extends Controller
{
    /**
     * Seconds a pending job may wait before the worker counts as stalled.
     *
     * Equal to the window after which the mentee is offered "Nilai ulang".
     */
    public const STALLED_AFTER_SECONDS = UserPracticeResponse::STALLED_AFTER_SECONDS;

    /**
     * Report whether the queue worker is taking jobs.
     */
    public function __invoke(): JsonResponse
    {
        $connection = config('queue.default');
        $config = config('queue.connections.'.$connection);

        if (! is_array($config) || ($config['driver'] ?? null) !== 'database') {
            return response()->json(['status' => 'ok', 'checks' => ['queue' => 'not_monitored']]);
        }

        try {
            $oldest_available_at = DB::connection($config['connection'] ?? null)
                ->table($config['table'] ?? 'jobs')
                ->whereNull('reserved_at')
                ->min('available_at');
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['status' => 'degraded', 'checks' => ['queue' => 'down']], 503);
        }

        $waiting_seconds = is_numeric($oldest_available_at)
            ? max(0, now()->getTimestamp() - (int) $oldest_available_at)
            : 0;

        $stalled = $waiting_seconds > self::STALLED_AFTER_SECONDS;

        return response()->json([
            'status' => $stalled ? 'degraded' : 'ok',
            'checks' => ['queue' => $stalled ? 'stalled' : 'ok'],
            'oldest_pending_seconds' => $waiting_seconds,
        ], $stalled ? 503 : 200);
    }
}

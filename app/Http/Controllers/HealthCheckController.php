<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Readiness probe for load balancers and uptime monitors.
 *
 * The liveness route (/up) only proves the application booted. This endpoint
 * also verifies the database and cache are reachable. It deliberately returns
 * no exception messages, credentials, paths or configuration values.
 */
class HealthCheckController extends Controller
{
    /**
     * Run the readiness checks.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        try {
            DB::select('select 1');
            $checks['database'] = 'ok';
        } catch (Throwable $exception) {
            report($exception);
            $checks['database'] = 'down';
            $healthy = false;
        }

        try {
            // A read is enough to prove the cache backend is reachable. The
            // database cache store shares the database connection checked above.
            Cache::get('health-check');
            $checks['cache'] = 'ok';
        } catch (Throwable $exception) {
            report($exception);
            $checks['cache'] = 'down';
            $healthy = false;
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}

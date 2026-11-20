<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers public and mentee requests with the "Skular sedang diperbarui" page
 * while UNDER_CONSTRUCTION is on (alias under.construction).
 *
 * routes/web.php only attaches this middleware when config('app.under_construction')
 * is true at route load; the runtime check below is a second guard so a stale
 * route cache can never keep the site closed after the switch is turned off.
 * Returns 503 with Retry-After so search engines keep the real pages indexed.
 */
class ShowUnderConstruction
{
    /**
     * Seconds a client or crawler is asked to wait before retrying.
     */
    public const RETRY_AFTER_SECONDS = 3600;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.under_construction')) {
            return $next($request);
        }

        $headers = ['Retry-After' => (string) self::RETRY_AFTER_SECONDS];

        if ($request->expectsJson()) {
            return response()->json(
                ['message' => __('Skular sedang diperbarui. Coba lagi sebentar lagi.')],
                Response::HTTP_SERVICE_UNAVAILABLE,
                $headers,
            );
        }

        return response()->view('under-construction', [], Response::HTTP_SERVICE_UNAVAILABLE, $headers);
    }
}

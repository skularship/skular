<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Slow Request Instrumentation
    |--------------------------------------------------------------------------
    |
    | Requests that take longer than this threshold are logged with the route,
    | status and duration only. Query strings, bodies, user content and
    | credentials are never logged.
    |
    */

    'slow_request_ms' => (int) env('PERFORMANCE_SLOW_REQUEST_MS', 1000),

    'log_slow_requests' => (bool) env('PERFORMANCE_LOG_SLOW_REQUESTS', true),

    /*
    |--------------------------------------------------------------------------
    | Slow Query Instrumentation
    |--------------------------------------------------------------------------
    |
    | Disabled by default because it adds a listener to every query. Enable it
    | temporarily on staging when investigating slow pages; only SQL text and
    | duration are logged, never bound values.
    |
    */

    'slow_query_ms' => (int) env('PERFORMANCE_SLOW_QUERY_MS', 200),

    'log_slow_queries' => (bool) env('PERFORMANCE_LOG_SLOW_QUERIES', false),

];

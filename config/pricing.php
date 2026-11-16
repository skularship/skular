<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Premium switch
    |--------------------------------------------------------------------------
    |
    | Off by default: while false, /premium returns 404 and the landing price
    | section, dashboard card and sidebar link stay hidden. Turn on (PREMIUM_ENABLED=true)
    | only after the Pro features exist and Midtrans keys are set.
    |
    */

    'enabled' => (bool) env('PREMIUM_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Launching promo
    |--------------------------------------------------------------------------
    |
    | Products with a price_launching use it until this moment (ISO 8601, e.g.
    | 2026-12-15T23:59:00+07:00). Leave empty to switch the promo off: every plan
    | then sells at its normal price. The end date must be a real one, because
    | the Premium page shows it to visitors.
    |
    */

    'launching_until' => env('PRICING_LAUNCHING_UNTIL'),

    /*
    |--------------------------------------------------------------------------
    | Default plan
    |--------------------------------------------------------------------------
    |
    | Product code preselected on the Premium page and the landing price cards.
    |
    */

    'default_plan' => 'pro_12bln',
];

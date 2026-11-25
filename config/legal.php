<?php

/*
|--------------------------------------------------------------------------
| Legal pages (Kebijakan Privasi, Syarat & Ketentuan, Cookie, Pengembalian Dana)
|--------------------------------------------------------------------------
|
| One source for every fact the legal pages print. The repository is public,
| so the company identity and contact addresses come from the environment;
| a value nobody has decided yet stays as an "[ISI: ...]" marker and
| `php artisan legal:check` fails in production until it is filled.
|
| Raise a document's `version` and `effective_at` whenever its content
| changes. Raising terms or privacy makes every signed-in mentee accept the
| new version once (EnsureLegalConsent); raising cookies asks every visitor
| for their cookie choice again.
|
*/

return [

    'company' => [
        'name' => env('LEGAL_COMPANY_NAME', '[ISI: nama badan usaha]'),
        'address' => env('LEGAL_COMPANY_ADDRESS', '[ISI: alamat badan usaha]'),
        'registration' => env('LEGAL_COMPANY_NIB', '[ISI: NIB]'),
    ],

    'contact' => [
        'email' => env('LEGAL_CONTACT_EMAIL', '[ISI: email bantuan]'),
        'privacy_email' => env('LEGAL_PRIVACY_EMAIL', '[ISI: email privasi]'),
    ],

    // Pengadilan negeri for disputes (Bagian A no. 8).
    'jurisdiction_city' => env('LEGAL_JURISDICTION_CITY', '[ISI: kota pengadilan negeri]'),

    // Where the production server and the mail provider are (Bagian A no. 9).
    'hosting' => [
        'server_location' => env('LEGAL_SERVER_LOCATION', '[ISI: lokasi server]'),
        'mail_provider' => env('LEGAL_MAIL_PROVIDER', '[ISI: penyedia email]'),
    ],

    /*
    | Version and effective date per document. Dates are Y-m-d.
    */
    'documents' => [
        'privacy' => [
            'version' => '1.3',
            'effective_at' => '2026-11-26',
        ],
        'terms' => [
            'version' => '1.0',
            'effective_at' => '2026-11-16',
        ],
        'cookies' => [
            'version' => '1.0',
            'effective_at' => '2026-11-16',
        ],
        'refund' => [
            'version' => '1.0',
            'effective_at' => '2026-11-16',
        ],
    ],

    /*
    | Refund rules (Bagian A no. 4 and 5, default proposal until the owner confirms).
    */
    'refund' => [
        // Full refund within this many days of payment while Pro is unused.
        'window_days' => 7,
        // Working days to review a request.
        'review_days' => 5,
        // true: the gateway fee is not deducted from a refund.
        'full_amount_including_fees' => true,
    ],

    /*
    | First-party cookie that keeps the visitor's cookie choice. It is set by
    | resources/js/cookie-consent.js, so it is excluded from cookie encryption
    | (bootstrap/app.php) and read on the server by partials/analytics.
    */
    'consent_cookie' => [
        'name' => 'skular_cookie_consent',
        'days' => 180,
    ],

    'features' => [
        // "Lolos dibayar kembali" appreciation programme: not built yet (Bagian A no. 6).
        'success_appreciation' => false,
    ],

    // Markers that must never reach production on a rendered legal page.
    'placeholder_markers' => ['[ISI', '[KEPUTUSAN'],

];

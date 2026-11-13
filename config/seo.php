<?php

/*
|--------------------------------------------------------------------------
| Technical SEO
|--------------------------------------------------------------------------
|
| Splitting the canonical host out of the request keeps every generated URL
| (canonical tag, sitemap, Open Graph) on one production domain. A loopback
| APP_URL is a local development address and must never reach a crawler, so
| it falls back to the configured production host, always over HTTPS.
|
*/

$canonicalUrl = (string) (env('SEO_CANONICAL_URL') ?: env('APP_URL', 'https://skularship.com'));
$canonicalHost = strtolower((string) parse_url($canonicalUrl, PHP_URL_HOST));

if ($canonicalHost === '' || in_array($canonicalHost, ['localhost', '127.0.0.1', '::1'], true)) {
    $canonicalUrl = 'https://skularship.com';
}

return [

    'site_name' => 'Skular',

    // Production canonical host: https, no trailing slash, no development host.
    'url' => rtrim((string) preg_replace('#^http://#i', 'https://', $canonicalUrl), '/'),

    'locale' => 'id_ID',

    'twitter_card' => 'summary_large_image',

    /*
    | Metadata for pages that do not pass their own. These are the landing page
    | values, since `/` is the public page Skular wants search engines to index.
    */
    'defaults' => [
        'title' => 'Skular — Platform Persiapan Beasiswa untuk Masa Depanmu',
        'description' => 'Isi sekali, daftar berkali-kali. Skular membantu persiapan beasiswamu: profiling potensi, rekomendasi beasiswa, modul belajar, dan latihan berskor dengan AI.',
    ],

    /*
    | Shared social preview, 1200 × 630 (1.91:1). It is a real public file, so
    | WhatsApp, Facebook, LinkedIn and X can fetch it without authentication.
    */
    'image' => [
        'path' => '/images/og-skular.jpg',
        'width' => 1200,
        'height' => 630,
        'alt' => 'Preview Skular — platform persiapan beasiswa',
    ],

    /*
    | Sitemap `lastmod` (Y-m-d) for pages whose content lives in a view file.
    | Change these dates by hand ONLY when the page copy really changes; a
    | deploy alone must not move them. /faq prefers the newest published FAQ
    | and falls back to its date here when nothing is published.
    */
    'pages' => [
        'home' => [
            'lastmod' => env('SEO_HOME_LASTMOD', '2026-11-13'),
        ],
        'faq' => [
            'lastmod' => env('SEO_FAQ_LASTMOD', '2026-11-06'),
        ],
    ],

];

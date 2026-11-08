{{-- Shared <head> contents: meta/SEO tags, color-scheme light, fonts and the Vite assets (app.css, app.js). --}}
@php
    /*
     * The single source of document metadata.
     *
     * Layouts and pages pass overrides through @include('partials.head', [...]):
     *   title, description, robots, canonical, ogType, ogImage, structuredData.
     * Anything not passed falls back to the landing page values in config/seo.php,
     * so a page can never end up with duplicated or competing meta tags.
     */
    $seoTitle = filled($title ?? null)
        ? $title.' — '.config('seo.site_name')
        : config('seo.defaults.title');

    $seoDescription = filled($description ?? null)
        ? $description
        : config('seo.defaults.description');

    $seoRobots = $robots ?? 'index, follow';
    $seoCanonical = $canonical ?? \App\Support\Seo::canonical();
    $seoType = $ogType ?? 'website';
    $seoImage = $ogImage ?? \App\Support\Seo::imageUrl();
    $seoImageAlt = config('seo.image.alt');
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="color-scheme" content="light" />

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">

<link rel="icon" href="/favicon.png" type="image/png">

<!-- Open Graph: the shared preview for WhatsApp, Facebook, LinkedIn and others. -->
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:site_name" content="{{ config('seo.site_name') }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="{{ config('seo.image.width') }}">
<meta property="og:image:height" content="{{ config('seo.image.height') }}">
<meta property="og:image:alt" content="{{ $seoImageAlt }}">
<meta property="og:locale" content="{{ config('seo.locale') }}">

<!-- X / Twitter reads the same values, so the two never disagree. -->
<meta name="twitter:card" content="{{ config('seo.twitter_card') }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
<meta name="twitter:image:alt" content="{{ $seoImageAlt }}">

@if (filled($structuredData ?? null))
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
@endif

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    document.addEventListener('alpine:init', function () {
        Alpine.store('sidebar', {
            open: false,
        });
    });
</script>

<?xml version="1.0" encoding="UTF-8"?>
{{-- XML sitemap rendered by SitemapController. Data: $urls (loc + lastmod). Keep <?xml on the first line. --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        <lastmod>{{ $url['lastmod'] }}</lastmod>
    </url>
@endforeach
</urlset>

{{-- Authenticated app layout (<x-layouts::app>); wraps layouts/app/sidebar. Data: optional $title. --}}
<x-layouts::app.sidebar :title="$title ?? null">
    {{$slot}}
</x-layouts::app.sidebar>
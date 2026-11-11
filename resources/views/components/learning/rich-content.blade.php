{{--
    Curriculum text rendered from Markdown with the brand content style (.sk-prose, resources/css/prose.css).
    Props: content (Markdown or legacy plain text), level (HTML level of a Markdown "#", default 4).
    The HTML comes from RichContentRendererInterface, which strips raw HTML and unsafe links, so echoing it unescaped is safe.
--}}
@props([
    'content' => null,
    'level' => 4,
])

@php
    $html = app(\App\Contracts\RichContentRendererInterface::class)->toHtml($content, (int) $level);
@endphp

@if ($html !== '')
    <div {{ $attributes->class(['sk-prose']) }} data-test="rich-content">{!! $html !!}</div>
@endif

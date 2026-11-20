{{--
    Curriculum text rendered with the brand content style (.sk-prose, resources/css/prose.css).
    Props: content (stored material), format ("markdown" default, or "html" for Inti Materi saved from the rich editor),
    level (HTML level of a top-level heading, default 4).
    The HTML comes from RichContentRendererInterface, which strips unsafe markup, links and styles, so echoing it unescaped is safe.
--}}
@props([
    'content' => null,
    'format' => 'markdown',
    'level' => 4,
])

@php
    $html = app(\App\Contracts\RichContentRendererInterface::class)->toHtmlFromFormat($content, $format, (int) $level);
@endphp

@if ($html !== '')
    <div {{ $attributes->class(['sk-prose']) }} data-test="rich-content">{!! $html !!}</div>
@endif

{{-- Landing-only clay call-to-action link (<x-landing.cta>). At least 44px tall, pressable, with a visible keyboard focus ring. Props: variant (primary/quiet/inverse), size (base/lg), iconTrailing. --}}
@props([
    'variant' => 'primary',
    'size' => 'base',
    'iconTrailing' => null,
])

@php
    $sizeClasses = match ($size) {
        'lg' => 'min-h-13 px-6 text-base',
        default => 'min-h-11 px-5 text-sm',
    };

    $variantClasses = match ($variant) {
        'quiet' => 'clay-sm clay-press bg-white text-brand-ink hover:bg-brand-cream',
        'inverse' => 'border-2 border-white/30 text-white hover:border-brand-amber hover:text-brand-amber',
        default => 'clay-sm clay-press bg-brand-amber text-brand-ink hover:bg-brand-gold',
    };
@endphp

<a {{ $attributes->class([
    'inline-flex items-center justify-center gap-2 rounded-2xl font-bold whitespace-nowrap transition-colors',
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-teal',
    $sizeClasses,
    $variantClasses,
]) }}>
    {{ $slot }}

    @if (filled($iconTrailing))
        <x-icons.app-icon :name="$iconTrailing" class="size-4" />
    @endif
</a>

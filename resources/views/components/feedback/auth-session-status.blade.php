{{-- Session status message on auth screens (e.g. reset link sent). Prop: status. --}}
@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        {{ $status }}
    </div>
@endif

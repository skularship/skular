{{--
    One feedback category card on the Praktik step (strengths, improvements or next steps).
    Data: $items (array<FeedbackPointData>), $title, $icon, $marker (list icon), $tone (emerald/amber/teal), $test (data-test).
    The main point is bold with its explanation underneath; legacy plain-string items (point = null) show the explanation only.
--}}
@php
    [$card_classes, $title_classes, $marker_classes] = match ($tone) {
        'emerald' => ['border-emerald-200 bg-emerald-50/80', 'text-emerald-700', 'text-emerald-500'],
        'amber' => ['border-amber-200 bg-amber-50/80', 'text-amber-700', 'text-amber-500'],
        default => ['border-brand-teal/25 bg-white/80', 'text-brand-teal', 'text-brand-orange'],
    };
@endphp

<div class="flex flex-col gap-3 rounded-2xl border p-4 sm:p-5 {{ $card_classes }}" data-test="{{ $test }}">
    <p class="inline-flex items-center gap-1.5 text-sm font-bold {{ $title_classes }}">
        <x-icons.app-icon :name="$icon" class="size-4" />
        {{ $title }}
    </p>

    <ul class="flex flex-col gap-3.5">
        @foreach ($items as $item)
            <li class="flex items-start gap-2.5">
                <x-icons.app-icon :name="$marker" class="mt-1 size-3.5 shrink-0 {{ $marker_classes }}" />

                <div class="flex min-w-0 flex-col gap-0.5 text-sm leading-relaxed">
                    @if (filled($item->point))
                        <strong class="font-semibold text-zinc-900">{{ $item->point }}</strong>
                    @endif

                    @if (filled($item->explanation))
                        <span class="text-zinc-700">{{ $item->explanation }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</div>

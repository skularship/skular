{{-- One plan card for the landing price section (<x-pricing.price-card>). Props: quote (App\Data\PriceQuote), featured, href, cta,
     features (list of array{0: string, 1: bool} = text, not released yet). Prices come from PricingService, never typed here. --}}
@props([
    'quote',
    'featured' => false,
    'href',
    'cta',
    'features' => [],
])

@php($has_ticket = $quote->is_launching && $quote->launching_until)

<div @class([
    'relative flex flex-col rounded-[1.75rem] border-2 bg-white',
    'border-brand-teal-light ring-5 ring-brand-teal-soft lg:-my-4' => $featured,
    'border-zinc-200' => ! $featured,
])>
    @if ($featured)
        <span class="absolute -top-3.5 start-6 rounded-full bg-brand-ink px-3 py-1 text-[0.7rem] font-bold tracking-wide text-white">{{ __('PALING POPULER') }}</span>
    @endif

    <div class="flex flex-1 flex-col p-6 sm:p-7">
        <div class="flex items-center justify-between gap-3">
            <p class="text-lg font-bold text-zinc-900">{{ $quote->months }} {{ __('bulan') }}</p>
            @if ($quote->saving_percent)
                <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">{{ __('Hemat :percent%', ['percent' => $quote->saving_percent]) }}</span>
            @endif
        </div>
        <p class="text-sm text-zinc-500">{{ $quote->name }}</p>

        <p class="mt-4 text-4xl font-extrabold tracking-tight text-zinc-900">
            {{ \App\Support\Rupiah::format($quote->monthly) }}<span class="text-base font-semibold text-zinc-500">/bln</span>
        </p>

        @if ($quote->strike_monthly)
            <p class="text-sm text-zinc-400 line-through">{{ \App\Support\Rupiah::format($quote->strike_monthly) }}/bln</p>
        @endif

        <p class="mt-1 text-sm text-zinc-600">
            {{ __('Sekali bayar') }} <strong class="font-bold text-zinc-900">{{ \App\Support\Rupiah::format($quote->amount) }}</strong> {{ __('untuk :months bulan', ['months' => $quote->months]) }}
        </p>

        @if ($features !== [])
            <ul class="mt-5 flex flex-col gap-2.5 border-t border-dashed border-zinc-200 pt-5 text-sm text-zinc-700">
                @foreach ($features as [$text, $soon])
                    <li class="flex items-start gap-2">
                        <x-icons.app-icon name="check" class="mt-0.5 size-4 text-brand-teal" />
                        <span>
                            {{ $text }}
                            @if ($soon)
                                <x-ui.pill variant="peach" class="ms-0.5">{{ __('Coming Soon') }}</x-ui.pill>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-auto pt-6">
            <x-landing.cta :href="$href" :variant="$featured ? 'primary' : 'quiet'" iconTrailing="arrow-right" class="w-full rounded-full">{{ $cta }}</x-landing.cta>
        </div>
    </div>

    {{-- Launch promo as a torn ticket stub --}}
    @if ($has_ticket)
        <div class="relative rounded-b-[1.6rem] border-t-2 border-dashed border-brand-orange/50 bg-brand-peach-soft/70 px-6 py-3">
            <span @class(['absolute -top-2.5 -start-3.5 size-5 rounded-full border-2 bg-warm-sand', 'border-brand-teal-light' => $featured, 'border-zinc-200' => ! $featured]) aria-hidden="true"></span>
            <span @class(['absolute -top-2.5 -end-3.5 size-5 rounded-full border-2 bg-warm-sand', 'border-brand-teal-light' => $featured, 'border-zinc-200' => ! $featured]) aria-hidden="true"></span>
            <p class="flex items-center justify-center gap-2 text-xs text-zinc-700">
                <x-icons.app-icon name="gift" class="size-4 text-brand-orange" />
                {{ __('Promo berakhir :date', ['date' => $quote->launching_until->translatedFormat('j F Y')]) }}
            </p>
        </div>
    @endif
</div>

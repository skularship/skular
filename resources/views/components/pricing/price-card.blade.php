{{-- One plan card for the landing price section (<x-pricing.price-card>). Props: quote (App\Data\PriceQuote), featured, href, cta. Prices come from PricingService, never typed here. --}}
@props([
    'quote',
    'featured' => false,
    'href',
    'cta',
])

<div @class([
    'clay relative flex flex-col gap-3 rounded-4xl bg-white p-6',
    'border-2 border-brand-teal' => $featured,
])>
    @if ($featured)
        <span class="absolute -top-3 start-6 rounded-full bg-brand-ink px-3 py-0.5 text-[0.7rem] font-bold tracking-wide text-white">{{ __('PALING POPULER') }}</span>
    @endif

    <div class="flex items-center justify-between gap-3">
        <p class="font-bold text-zinc-900">{{ $quote->name }}</p>
        @if ($quote->saving_percent)
            <span class="rounded-full bg-red-600 px-2.5 py-0.5 text-xs font-bold text-white">{{ __('Hemat :percent%', ['percent' => $quote->saving_percent]) }}</span>
        @endif
    </div>

    <p class="text-4xl font-extrabold tracking-tight text-zinc-900">
        {{ \App\Support\Rupiah::format($quote->monthly) }}<span class="text-sm font-semibold text-zinc-500">/bln</span>
    </p>

    @if ($quote->strike_monthly)
        <p class="-mt-2 text-sm text-zinc-400 line-through">{{ \App\Support\Rupiah::format($quote->strike_monthly) }}/bln</p>
    @endif

    <p class="text-sm text-zinc-600">{{ __('Sekali bayar :price untuk :months bulan', ['price' => \App\Support\Rupiah::format($quote->amount), 'months' => $quote->months]) }}</p>

    @if ($quote->is_launching && $quote->launching_until)
        <p class="rounded-2xl border border-dashed border-brand-orange/60 bg-brand-peach-soft/60 px-3 py-2 text-xs text-zinc-700">
            {{ __('Promo berakhir :date', ['date' => $quote->launching_until->translatedFormat('j F Y')]) }}
        </p>
    @endif

    <x-landing.cta :href="$href" :variant="$featured ? 'primary' : 'quiet'" class="mt-auto w-full">{{ $cta }}</x-landing.cta>
</div>

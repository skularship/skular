{{-- "Paket dan Tagihan" (App\Livewire\Premium\Billing): current Pro plan and transaction history. --}}
@use('App\Support\Rupiah')

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 py-6">
    <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900">{{ __('Paket dan Tagihan') }}</h1>

    <x-ui.card class="flex flex-col gap-3" data-test="billing-plan">
        @if ($has_pro && $subscription)
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.pill variant="success" icon="check">{{ __('Pro aktif') }}</x-ui.pill>
                <span class="font-bold text-zinc-900">{{ $subscription->plan === 'pro_12bln' ? __('Pro 12 bulan') : __('Pro 1 bulan') }}</span>
            </div>
            <p class="text-zinc-600">{{ __('Aktif sampai :date. Membeli lagi menyambung dari tanggal itu, harganya harga normal.', ['date' => $subscription->active_until->translatedFormat('j F Y')]) }}</p>
            <x-actions.link-button :href="route('premium')" wire:navigate class="w-fit">{{ __('Perpanjang') }}</x-actions.link-button>
        @else
            <p class="font-bold text-zinc-900">{{ __('Paket Gratis') }}</p>
            <p class="text-zinc-600">
                @if ($subscription)
                    {{ __('Pro kamu berakhir pada :date. Datamu tetap aman.', ['date' => $subscription->active_until->translatedFormat('j F Y')]) }}
                @else
                    {{ __('Info semua beasiswa gratis. Pro membuka alat untuk menyiapkan banyak beasiswa sekaligus.') }}
                @endif
            </p>
            <x-actions.link-button :href="route('premium')" wire:navigate class="w-fit">{{ __('Lihat paket Pro') }}</x-actions.link-button>
        @endif
    </x-ui.card>

    <x-ui.card padding="none" class="overflow-hidden" data-test="billing-history">
        <h2 class="border-b border-zinc-100 px-5 py-4 text-sm font-bold tracking-wide text-zinc-500 uppercase">{{ __('Riwayat Transaksi') }}</h2>

        @forelse ($orders as $order)
            <a href="{{ route('payment.status', $order) }}" wire:navigate class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 last:border-b-0 hover:bg-brand-cream">
                <div>
                    <p class="font-semibold text-zinc-900">{{ $order->product->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $order->created_at?->translatedFormat('j F Y, H:i') }} &middot; {{ $order->order_id }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-semibold text-zinc-800">{{ Rupiah::format($order->amount) }}</span>
                    <x-ui.pill :variant="$order->status->getColor()">{{ $order->status->getLabel() }}</x-ui.pill>
                </div>
            </a>
        @empty
            <p class="px-5 py-6 text-sm text-zinc-500">{{ __('Belum ada transaksi.') }}</p>
        @endforelse
    </x-ui.card>
</div>

{{-- Payment status (App\Livewire\Premium\Status). Pro is activated by the Midtrans webhook; while waiting, "Cek status pembayaran" asks Midtrans directly. --}}
@use('App\Support\Rupiah')
@use('App\Enums\OrderStatus')

<div class="mx-auto flex w-full max-w-xl flex-col gap-6 py-8" @if ($is_pending) wire:poll.5s @endif>
    <x-ui.card class="flex flex-col items-center gap-4 text-center" data-test="payment-status">
        @if ($order->status === OrderStatus::Paid)
            <x-ui.icon-badge icon="check" tone="mint" size="lg" solid />
            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900">{{ __('Pembayaran berhasil') }}</h1>
            <p class="text-zinc-600">
                @if ($pro_until)
                    {{ __('Pro aktif sampai :date.', ['date' => $pro_until->translatedFormat('j F Y')]) }}
                @else
                    {{ __('Pro kamu sedang diaktifkan.') }}
                @endif
            </p>
            <x-actions.link-button :href="route('dashboard')" wire:navigate iconTrailing="arrow-right">{{ __('Lanjut ke dashboard') }}</x-actions.link-button>
        @elseif ($is_pending)
            <x-ui.icon-badge icon="clock" tone="amber" size="lg" solid />
            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900">{{ __('Menunggu pembayaran') }}</h1>
            <p class="text-zinc-600">{{ __('Selesaikan pembayaranmu. Halaman ini diperbarui otomatis setelah pembayaran masuk.') }}</p>
            <div class="flex flex-wrap justify-center gap-3">
                <x-actions.button wire:click="checkStatus" icon="arrow-path" loading-text="{{ __('Mengecek...') }}" data-test="payment-check">{{ __('Cek status pembayaran') }}</x-actions.button>
                <x-actions.link-button :href="route('premium', ['paket' => $order->product->code])" variant="outline">{{ __('Buka lagi halaman pembayaran') }}</x-actions.link-button>
            </div>
            @if ($notice)
                <p class="text-sm text-zinc-600" role="status" data-test="payment-check-notice">{{ $notice }}</p>
            @endif
        @else
            <x-ui.icon-badge icon="x-mark" tone="rose" size="lg" solid />
            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900">{{ __('Pembayaran belum berhasil') }}</h1>
            <p class="text-zinc-600">{{ __('Kamu belum ditagih. Kamu bisa mencoba lagi kapan saja.') }}</p>
            <x-actions.link-button :href="route('premium', ['paket' => $order->product->code])">{{ __('Coba lagi') }}</x-actions.link-button>
        @endif

        <dl class="mt-2 grid w-full grid-cols-2 gap-3 border-t border-zinc-100 pt-4 text-start text-sm">
            <dt class="text-zinc-500">{{ __('Paket') }}</dt>
            <dd class="font-semibold text-zinc-800">{{ $order->product->name }}</dd>
            <dt class="text-zinc-500">{{ __('Jumlah') }}</dt>
            <dd class="font-semibold text-zinc-800">{{ Rupiah::format($order->amount) }}</dd>
            <dt class="text-zinc-500">{{ __('Nomor pesanan') }}</dt>
            <dd class="font-mono text-xs font-semibold break-all text-zinc-800">{{ $order->order_id }}</dd>
        </dl>
    </x-ui.card>

    <a href="https://wa.me/{{ config('services.support.whatsapp') }}?text={{ rawurlencode('Halo Skular, saya mau tanya soal pesanan '.$order->order_id) }}" target="_blank" rel="noopener" class="text-center text-sm font-semibold text-brand-teal hover:underline">
        {{ __('Tanya lewat WhatsApp') }}
    </a>
</div>

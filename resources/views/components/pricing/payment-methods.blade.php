{{-- Payment method strip (<x-pricing.payment-methods>) shown next to Pro checkout buttons: methods as text marks, plus the no auto-renew note. No props; pass classes to align it. --}}
<p {{ $attributes->class(['flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500']) }}>
    <span class="rounded-md border border-zinc-300 px-1.5 py-px text-[0.6875rem] font-extrabold tracking-wide text-zinc-800">QRIS</span>
    <span class="rounded-md border border-zinc-300 px-1.5 py-px text-[0.6875rem] font-extrabold text-sky-700">GoPay</span>
    <span class="rounded-md border border-zinc-300 px-1.5 py-px text-[0.6875rem] font-bold text-zinc-700">{{ __('VA') }}</span>
    <span class="rounded-md border border-zinc-300 px-1.5 py-px text-[0.6875rem] font-bold text-zinc-700">{{ __('Kartu') }}</span>
    <span>{{ __('Aktif setelah bayar. Tanpa auto-renew.') }}</span>
</p>

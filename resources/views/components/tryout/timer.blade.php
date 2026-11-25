{{-- Countdown pill of a Try Out subtest (<x-tryout.timer>). The deadline comes from the server; the browser only
     displays it (corrected for clock skew with serverNow). Teal normally, orange under 5 minutes, pulsing under
     1 minute. After deadline + grace it calls $wire.expire() once; the server decides. Props: deadline, serverNow, grace. --}}
@props([
    'deadline',
    'serverNow' => null,
    'grace' => 5,
])

<div
    wire:ignore
    x-data="{
        deadline: Date.parse(@js($deadline)),
        offset: @js($serverNow) ? Date.parse(@js($serverNow)) - Date.now() : 0,
        left: 0,
        fired: false,
        timer: null,
        tick() {
            this.left = Math.max(0, Math.floor((this.deadline - (Date.now() + this.offset)) / 1000));
            const overdue = (Date.now() + this.offset) - this.deadline;
            if (! this.fired && overdue > ({{ (int) $grace }} + 1) * 1000) {
                this.fired = true;
                $wire.expire();
            }
        },
        get label() {
            const h = Math.floor(this.left / 3600);
            const m = Math.floor((this.left % 3600) / 60);
            const s = this.left % 60;
            const pad = (n) => String(n).padStart(2, '0');
            return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
        },
    }"
    x-init="tick(); timer = setInterval(() => tick(), 1000)"
    x-on:livewire:navigating.window="clearInterval(timer)"
    :class="left < 60 ? 'bg-brand-orange text-white animate-pulse' : (left < 300 ? 'bg-brand-orange text-white' : 'bg-white text-brand-teal-deep')"
    class="flex h-10 items-center gap-2 rounded-full px-4 font-extrabold tabular-nums shadow-soft transition-colors"
    role="timer"
    aria-live="off"
    data-test="tryout-timer"
>
    <x-icons.app-icon name="clock" class="size-5" />
    <span x-text="label" class="text-base sm:text-lg"></span>
</div>

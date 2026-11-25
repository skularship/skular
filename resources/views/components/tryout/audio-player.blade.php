{{-- Listening audio card (<x-tryout.audio-player>). Every play first asks the server ($wire.playAudio()), which counts
     plays per unit and refuses once the mode limit is reached. No seek control; exam mode plays once.
     Props: unit (TryoutUnitViewData array), label (e.g. "Bagian 2"). --}}
@props([
    'unit',
    'label' => null,
])

@php
    $plays_left = max(0, (int) $unit['plays_allowed'] - (int) $unit['plays_used']);
@endphp

<div
    wire:key="audio-{{ $unit['code'] }}"
    x-data="{
        playing: false,
        loading: false,
        current: 0,
        duration: {{ (int) ($unit['audio_duration_seconds'] ?? 0) }},
        playsLeft: {{ $plays_left }},
        format(sec) { sec = Math.floor(sec || 0); return String(Math.floor(sec / 60)).padStart(2, '0') + ':' + String(sec % 60).padStart(2, '0'); },
        async play() {
            if (this.playing || this.loading || this.playsLeft <= 0) return;
            this.loading = true;
            const allowed = await $wire.playAudio();
            this.loading = false;
            if (! allowed) { this.playsLeft = 0; return; }
            this.playsLeft--;
            this.$refs.audio.currentTime = 0;
            this.$refs.audio.play();
        },
    }"
    class="flex items-center gap-4 rounded-4xl border border-brand-teal/10 bg-white p-4 shadow-soft sm:p-5"
    data-test="audio-player"
>
    <audio
        x-ref="audio"
        preload="auto"
        src="{{ $unit['audio_url'] }}"
        x-on:play="playing = true"
        x-on:pause="playing = false"
        x-on:ended="playing = false"
        x-on:timeupdate="current = $event.target.currentTime"
        x-on:loadedmetadata="duration = duration || $event.target.duration"
    ></audio>

    <button
        type="button"
        x-on:click="play()"
        :disabled="playing || loading || playsLeft <= 0"
        class="flex size-14 shrink-0 items-center justify-center rounded-full bg-brand-teal text-white shadow-soft transition hover:bg-brand-teal-deep disabled:bg-zinc-300 sm:size-16"
        aria-label="{{ __('Putar audio') }}"
        data-test="play-audio"
    >
        <x-icons.app-icon name="play" variant="s" class="size-6" x-show="! playing && ! loading" />
        <x-icons.app-icon name="speaker-wave" variant="s" class="size-6 animate-pulse" x-show="playing" x-cloak />
        <x-icons.app-icon name="spinner" class="size-6 animate-spin" x-show="loading" x-cloak />
    </button>

    <div class="min-w-0 flex-1">
        <p class="text-xs font-extrabold tracking-wide text-brand-teal uppercase">{{ $label ?? __('Audio') }}</p>
        <p class="truncate text-base font-extrabold text-zinc-900">{{ $unit['title'] }}</p>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-brand-teal-soft">
            <div class="h-full rounded-full bg-brand-teal transition-[width]" :style="`width: ${duration ? Math.min(100, current / duration * 100) : 0}%`"></div>
        </div>
        <p class="mt-1 text-xs text-zinc-500 tabular-nums"><span x-text="format(current)"></span> / <span x-text="format(duration)"></span></p>
    </div>

    <span
        :class="playsLeft > 0 ? 'bg-brand-amber-soft text-amber-900' : 'bg-zinc-100 text-zinc-500'"
        class="hidden shrink-0 rounded-full px-3 py-1.5 text-xs font-bold sm:inline-flex"
    >
        <span x-show="playsLeft > 0">{{ __('Sisa putar:') }} <span x-text="playsLeft"></span> {{ __('dari') }} {{ (int) $unit['plays_allowed'] }}</span>
        <span x-show="playsLeft <= 0" x-cloak>{{ __('Audio sudah diputar') }}</span>
    </span>
</div>

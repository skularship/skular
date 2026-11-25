{{-- Tes IQ runner (App\Livewire\Iq\Runner), mobile first. Sticky header with server timer, progress of answered
     questions and "Kirim" (confirmation). One question card: subtest label, reading text (Teks), image with alt text
     (Visuospasial), the prompt and four large A-D buttons; a pick is saved at once and can be changed. Then
     Sebelumnya / Berikutnya and the number grid. A support message (from the mentee's own counts only) slides in
     above the card for a few seconds and never covers the question.
     Data: question (view array, no key), answered (positions), answered_count, total, progress, deadline, server_now, grace. --}}
@php
    $position = (int) ($question['position'] ?? 1);
    $unanswered = max(0, $total - $answered_count);
@endphp

<div>
    <x-tryout.header
        :title="__('Tes IQ Latihan')"
        :mobile-title="__('Tes IQ · :n/:total', ['n' => $position, 'total' => $total])"
        :progress-label="__(':n dari :total dijawab', ['n' => $answered_count, 'total' => $total])"
        :progress="$progress"
        :deadline="$deadline"
        :server-now="$server_now"
        :grace="$grace"
        :finish-label="__('Kirim jawaban')"
        :finish-short-label="__('Kirim')"
        finish-action="submit"
        :confirm-text="$unanswered > 0
            ? __('Masih ada :n soal yang belum dijawab. Setelah dikirim, jawaban tidak bisa diubah lagi.', ['n' => $unanswered])
            : __('Semua soal sudah dijawab. Setelah dikirim, jawaban tidak bisa diubah lagi.')"
    />

    @if ($question !== [])
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-4 px-4 py-5 sm:px-6">
            {{-- Support message: in the page flow, above the card, gone after a few seconds --}}
            <div
                x-data="{ message: '', timer: null }"
                x-on:iq-support.window="message = $event.detail.message; clearTimeout(timer); timer = setTimeout(() => message = '', 5000)"
                aria-live="polite"
            >
                <p
                    x-cloak
                    x-show="message !== ''"
                    x-transition.opacity.duration.300ms
                    x-text="message"
                    class="rounded-2xl bg-brand-mint-soft px-4 py-3 text-sm font-semibold text-emerald-800"
                    data-test="iq-support-message"
                ></p>
            </div>

            <section class="rounded-4xl border border-brand-teal/10 bg-white p-5 shadow-soft sm:p-7" wire:key="iq-question-{{ $position }}" data-test="iq-question">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm font-extrabold text-brand-teal">{{ __('Soal :n dari :total', ['n' => $position, 'total' => $total]) }}</span>
                    <span class="rounded-full bg-brand-teal-soft px-3 py-1 text-xs font-bold text-brand-teal-deep" data-test="iq-subtest">{{ $question['subtest_label'] }}</span>
                </div>

                @if (filled($question['passage']))
                    <div class="mt-4 rounded-2xl bg-brand-cream px-4 py-3 text-[15px] leading-relaxed whitespace-pre-line text-zinc-800" data-test="iq-passage">{{ $question['passage'] }}</div>
                @endif

                @if (filled($question['image_url']))
                    <img
                        src="{{ $question['image_url'] }}"
                        alt="{{ $question['image_alt'] }}"
                        class="mt-4 max-h-[60vh] w-full rounded-2xl border border-zinc-100 bg-white object-contain"
                        data-test="iq-image"
                    >
                @endif

                <h1 class="mt-4 text-lg leading-snug font-extrabold whitespace-pre-line text-zinc-900 sm:text-xl">{{ $question['prompt'] }}</h1>

                <div class="mt-5 flex flex-col gap-3" role="radiogroup" x-data="{ picked: @js($question['choice']) }">
                    @foreach ($question['options'] as $letter => $text)
                        <button
                            type="button"
                            role="radio"
                            x-on:click="picked = @js($letter); $wire.choose(@js($letter))"
                            :aria-checked="picked === @js($letter) ? 'true' : 'false'"
                            :class="picked === @js($letter)
                                ? 'border-brand-teal bg-brand-teal-soft ring-2 ring-brand-teal/20'
                                : 'border-zinc-200 bg-white hover:border-brand-teal/50 hover:bg-brand-cream'"
                            class="group flex min-h-14 w-full items-center gap-4 rounded-2xl border-2 px-4 py-3 text-start transition"
                            data-test="iq-option-{{ $letter }}"
                        >
                            <span
                                :class="picked === @js($letter) ? 'bg-brand-teal text-white' : 'bg-zinc-100 text-zinc-600 group-hover:bg-brand-teal-soft'"
                                class="flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-extrabold transition"
                            >{{ $letter }}</span>
                            <span class="text-base font-medium text-zinc-900">{{ $text }}</span>
                        </button>
                    @endforeach
                </div>

                <p class="mt-3 flex h-5 items-center gap-1.5 text-xs text-zinc-500">
                    <span wire:loading wire:target="choose">{{ __('Menyimpan...') }}</span>
                    <span wire:loading.remove wire:target="choose">
                        @if ($question['choice'])
                            <span class="inline-flex items-center gap-1"><x-icons.app-icon name="check-circle" class="size-3.5 text-emerald-600" />{{ __('Jawaban tersimpan. Kamu masih bisa menggantinya sebelum dikirim.') }}</span>
                        @endif
                    </span>
                </p>
            </section>

            <div class="grid grid-cols-2 gap-3">
                <button type="button" wire:click="previous" @disabled($position <= 1) class="flex h-12 items-center justify-center gap-2 rounded-2xl border-2 border-zinc-200 bg-white font-bold text-zinc-700 transition hover:border-brand-teal/50 disabled:opacity-40" data-test="iq-previous">
                    <x-icons.app-icon name="chevron-left" class="size-4" /> {{ __('Sebelumnya') }}
                </button>
                @if ($position < $total)
                    <button type="button" wire:click="next" class="flex h-12 items-center justify-center gap-2 rounded-2xl bg-brand-teal font-bold text-white shadow-soft transition hover:bg-brand-teal-deep" data-test="iq-next">
                        {{ __('Berikutnya') }} <x-icons.app-icon name="chevron-right" class="size-4" />
                    </button>
                @else
                    <button type="button" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" class="flex h-12 items-center justify-center gap-2 rounded-2xl bg-brand-amber font-bold text-brand-ink shadow-soft" data-test="iq-last">
                        {{ __('Soal terakhir') }}
                    </button>
                @endif
            </div>

            <details class="group rounded-3xl border border-brand-teal/10 bg-white p-4 shadow-soft" open>
                <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-zinc-800">
                    {{ __('Nomor soal') }}
                    <span class="text-xs font-semibold text-zinc-500">{{ __(':n dari :total dijawab', ['n' => $answered_count, 'total' => $total]) }}</span>
                </summary>
                <div class="mt-3 grid grid-cols-7 gap-2 sm:grid-cols-10" data-test="iq-number-grid">
                    @for ($n = 1; $n <= $total; $n++)
                        <button
                            type="button"
                            wire:click="goTo({{ $n }})"
                            wire:key="iq-nav-{{ $n }}"
                            @class([
                                'flex h-10 items-center justify-center rounded-xl text-sm font-bold transition',
                                'bg-brand-teal-deep text-white ring-2 ring-brand-amber' => $n === $position,
                                'bg-brand-teal text-white' => $n !== $position && in_array($n, $answered, true),
                                'bg-zinc-100 text-zinc-600 hover:bg-brand-teal-soft' => $n !== $position && ! in_array($n, $answered, true),
                            ])
                            aria-label="{{ __('Soal :n', ['n' => $n]) }}"
                        >{{ $n }}</button>
                    @endfor
                </div>
            </details>

            <p class="text-center text-xs text-zinc-500">{{ config('iq.disclaimer') }}</p>
        </div>
    @endif
</div>

{{-- Writing subtest (App\Livewire\Tryout\Sections\WritingSection): Task 1 / Task 2 tabs with live word counts,
     prompt (and data table / chart) on the left, editor on the right, autosave status and the minimum word badge
     (red below the minimum). Free movement between tasks (D9). Data: prompts, texts, active, saved_at + header data. --}}
@php
    $autosave_ms = (int) config('tryout.writing.autosave_seconds', 10) * 1000;
@endphp

<div x-data="{
    counts: {},
    count(text) { const t = (text || '').trim(); return t === '' ? 0 : t.split(/\s+/).length; },
}">
    <x-tryout.header
        :title="$tryout_code.' · Writing'"
        mobile-title="Writing"
        finish-short-label="Kirim"
        :deadline="$deadline"
        :server-now="$server_now"
        :grace="$grace"
        :skills="$skills"
        :finish-label="__('Kirim Writing')"
        finish-action="submitWriting"
        :confirm-text="__('Kedua task akan disimpan lalu dinilai AI. Setelah dikirim, jawaban Writing tidak bisa diubah.')"
    />

    <div class="border-b border-brand-teal/10 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 py-3 sm:px-6">
            @foreach ($prompts as $index => $prompt)
                <button
                    type="button"
                    wire:click="switchTask({{ $index }})"
                    @class([
                        'h-11 rounded-xl px-4 text-sm font-bold transition',
                        'bg-brand-teal text-white shadow-soft' => $active === $index,
                        'bg-zinc-100 text-zinc-700 hover:bg-brand-teal-soft' => $active !== $index,
                    ])
                    data-test="task-tab-{{ $index + 1 }}"
                >
                    Task {{ $prompt['part'] ?? $index + 1 }} · <span x-text="count($wire.texts[{{ $index }}])">{{ str_word_count($texts[$index] ?? '') }}</span> {{ __('kata') }}
                </button>
            @endforeach
            <p class="ms-auto hidden text-sm text-zinc-500 md:block">{{ __('Saran waktu: Task 1 20 menit, Task 2 40 menit') }}</p>
        </div>
    </div>

    @php $prompt = $prompts[$active] ?? null; @endphp

    @if ($prompt)
        @php $min = (int) ($prompt['min_words'] ?? 0); @endphp

        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[2fr_3fr]" wire:key="task-{{ $prompt['code'] }}">
            <section class="flex flex-col gap-4 rounded-4xl border border-brand-teal/10 bg-white p-6 shadow-soft sm:p-7">
                <p class="text-xs font-extrabold tracking-wide text-brand-teal uppercase">
                    Task {{ $prompt['part'] ?? $active + 1 }}@if ($min) · {{ __('minimal :n kata', ['n' => $min]) }}@endif
                </p>
                <p class="text-base leading-relaxed whitespace-pre-line text-zinc-800">{{ $prompt['prompt'] }}</p>
                <x-tryout.data-table :table="$prompt['prompt_table']" />
                @if (filled($prompt['prompt_image_url']))
                    <img src="{{ $prompt['prompt_image_url'] }}" alt="{{ __('Grafik Task 1') }}" class="w-full rounded-2xl border border-brand-teal/10">
                @endif
            </section>

            <section class="flex flex-col rounded-4xl border border-brand-teal/10 bg-white p-6 shadow-soft sm:p-7">
                <label for="essay-{{ $active }}" class="text-sm font-extrabold text-zinc-900">{{ __('Jawabanmu') }}</label>
                <textarea
                    id="essay-{{ $active }}"
                    wire:model.live.debounce.{{ $autosave_ms }}ms="texts.{{ $active }}"
                    rows="16"
                    spellcheck="false"
                    autocorrect="off"
                    autocapitalize="off"
                    class="mt-3 min-h-[22rem] w-full flex-1 resize-y rounded-2xl border-2 border-brand-teal-soft p-4 text-base leading-relaxed text-zinc-800 focus:border-brand-teal focus:ring-brand-teal/20"
                    placeholder="{{ __('Tulis jawabanmu dalam bahasa Inggris...') }}"
                    data-test="essay-input"
                ></textarea>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="flex items-center gap-1.5 text-sm text-zinc-500">
                        <span wire:loading.remove wire:target="texts">
                            @if ($saved_at)
                                <x-icons.app-icon name="check" class="inline size-4 text-brand-teal" /> {{ __('Tersimpan otomatis :time', ['time' => $saved_at]) }}
                            @else
                                {{ __('Tersimpan otomatis tiap :n detik', ['n' => (int) ($autosave_ms / 1000)]) }}
                            @endif
                        </span>
                        <span wire:loading wire:target="texts">{{ __('Menyimpan...') }}</span>
                    </p>
                    <span
                        x-data="{ min: {{ $min }} }"
                        :class="count($wire.texts[{{ $active }}]) >= min ? 'bg-brand-mint-soft text-emerald-800' : 'bg-brand-rose-soft text-red-700'"
                        class="rounded-full px-3 py-1.5 text-sm font-bold tabular-nums"
                        data-test="word-counter"
                    >
                        <span x-text="count($wire.texts[{{ $active }}])"></span> / {{ $min }} {{ __('kata') }}
                    </span>
                </div>
            </section>
        </div>
    @else
        <x-ui.empty-state icon="exclamation-triangle" :title="__('Soal Writing belum tersedia')" :description="__('Hubungi tim Skular lewat menu Bantuan ya.')" class="mx-auto mt-10 max-w-xl" />
    @endif
</div>

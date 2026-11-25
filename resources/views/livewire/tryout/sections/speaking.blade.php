{{-- Speaking subtest (App\Livewire\Tryout\Sections\SpeakingSection): part tabs, the cue card / questions with
     unscored preparation notes, and the recorder (Alpine "tryoutSpeechRecorder", resources/js/speech-recorder.js).
     The browser transcribes the answer live with the Web Speech API (free, no upload); Part 2 runs a preparation
     countdown first and recording stops at the speaking limit. The mentee can play the take back locally, then
     sends only the transcript. Browsers without speech recognition get a notice.
     Data: prompts, active, states (sent, transcript), language + header data. --}}
@php
    $prompt = $prompts[$active] ?? null;
    $sent_count = collect($states)->where('sent', true)->count();
@endphp

<div>
    <x-tryout.header
        :title="$tryout_code.' · Speaking'"
        mobile-title="Speaking"
        finish-short-label="Kirim"
        :deadline="$deadline"
        :server-now="$server_now"
        :grace="$grace"
        :skills="$skills"
        :finish-label="__('Kirim Speaking')"
        finish-action="submitSpeaking"
        :confirm-text="$sent_count < count($prompts)
            ? __('Baru :sent dari :total part yang terkirim. Part yang belum direkam dinilai kosong.', ['sent' => $sent_count, 'total' => count($prompts)])
            : __('Semua part sudah terkirim. Kirim Speaking untuk dinilai AI?')"
    />

    <div class="border-b border-brand-teal/10 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-2 px-4 py-3 sm:px-6">
            @foreach ($prompts as $index => $part)
                <button
                    type="button"
                    wire:click="goToPart({{ $index }})"
                    @class([
                        'flex h-10 items-center gap-1.5 rounded-full px-4 text-sm font-bold transition',
                        'bg-brand-amber text-brand-ink shadow-soft' => $active === $index,
                        'bg-brand-mint-soft text-emerald-800' => $active !== $index && $states[$index]['sent'],
                        'bg-zinc-100 text-zinc-600' => $active !== $index && ! $states[$index]['sent'],
                    ])
                    data-test="part-tab-{{ $index + 1 }}"
                >
                    @if ($states[$index]['sent'])
                        <x-icons.app-icon name="check" class="size-4" />
                    @endif
                    Part {{ $part['part'] ?? $index + 1 }}{{ $states[$index]['sent'] ? ' '.__('terkirim') : '' }}
                </button>
            @endforeach
        </div>
    </div>

    @if ($prompt)
        @php
            $prep = (int) ($prompt['prep_seconds'] ?? 0);
            $max = (int) ($prompt['speak_seconds'] ?? 120);
        @endphp

        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-2" wire:key="part-{{ $prompt['code'] }}">
            <section class="flex flex-col gap-4 rounded-4xl border border-brand-teal/10 bg-white p-6 shadow-soft sm:p-8">
                <p class="text-xs font-extrabold tracking-wide text-brand-teal uppercase">
                    Part {{ $prompt['part'] ?? $active + 1 }}{{ $prep > 0 ? ' · '.__('Cue card') : '' }}
                </p>
                <h2 class="text-2xl leading-tight font-extrabold text-zinc-900">{{ $prompt['prompt'] }}</h2>

                @if ($prompt['prompt_items'] !== [])
                    @if ($prep > 0)
                        <p class="text-sm font-bold text-zinc-600">{{ __('You should say:') }}</p>
                    @endif
                    <ul class="flex flex-col gap-2 text-base text-zinc-800">
                        @foreach ($prompt['prompt_items'] as $item)
                            <li class="flex gap-2"><span class="mt-2.5 size-1.5 shrink-0 rounded-full bg-brand-teal"></span>{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif

                <label class="mt-2 flex flex-col gap-2">
                    <span class="text-sm font-bold text-zinc-800">{{ __('Catatan persiapan (tidak dinilai)') }}</span>
                    <textarea wire:ignore rows="5" class="rounded-2xl border-2 border-brand-cream-deep bg-brand-cream/40 p-3 text-sm focus:border-brand-teal focus:ring-brand-teal/20" placeholder="{{ __('Tulis poin-poin singkat di sini...') }}"></textarea>
                </label>
            </section>

            <section
                wire:key="recorder-{{ $prompt['code'] }}"
                x-data="tryoutSpeechRecorder({ index: {{ $active }}, prep: {{ $prep }}, max: {{ $max }}, lang: @js($language) })"
                class="flex flex-col items-center gap-5 rounded-4xl border border-brand-teal/10 bg-white p-6 text-center shadow-lift sm:p-8"
                data-test="recorder"
            >
                {{-- Browsers without speech recognition (Firefox) cannot answer Speaking. --}}
                <div x-show="! supported" x-cloak class="flex w-full flex-col items-center gap-3 rounded-3xl bg-brand-amber-soft p-6 text-amber-900" data-test="speech-unsupported">
                    <x-icons.app-icon name="exclamation-triangle" class="size-8" />
                    <p class="font-extrabold">{{ __('Browser ini belum bisa mengenali suara') }}</p>
                    <p class="text-sm">{{ __('Buka halaman ini di Google Chrome atau Microsoft Edge (laptop atau Android) untuk mengerjakan Speaking. Waktumu tetap berjalan, jadi pindah browser secepatnya ya.') }}</p>
                </div>

                <template x-if="supported">
                    <div class="flex w-full flex-col items-center gap-5">
                        @if ($states[$active]['sent'])
                            <div class="w-full rounded-2xl bg-brand-mint-soft p-4 text-start text-sm text-emerald-900" x-show="phase === 'idle'">
                                <p class="font-bold">{{ __('Jawaban part ini sudah terkirim.') }}</p>
                                <p class="mt-1 line-clamp-3 text-emerald-800/90">{{ $states[$active]['transcript'] !== '' ? $states[$active]['transcript'] : __('(tidak ada suara yang tertangkap)') }}</p>
                                <p class="mt-2 text-emerald-800/80">{{ __('Kamu boleh merekam ulang. Jawaban baru menggantikan yang lama.') }}</p>
                            </div>
                        @endif

                        <span
                            class="rounded-full px-4 py-1.5 text-sm font-bold"
                            :class="{
                                'bg-zinc-100 text-zinc-600': phase === 'idle',
                                'bg-brand-amber-soft text-amber-900': phase === 'prep',
                                'bg-brand-rose-soft text-red-700': phase === 'recording' || phase === 'finishing',
                                'bg-brand-teal-soft text-brand-teal-deep': phase === 'recorded' || phase === 'sending',
                            }"
                            x-text="{ idle: @js(__('Siap merekam')), prep: @js(__('Waktu persiapan')), recording: @js(__('Sedang merekam')), finishing: @js(__('Menyelesaikan...')), recorded: @js(__('Cek jawabanmu sebelum mengirim')), sending: @js(__('Mengirim...')) }[phase]"
                        ></span>

                        <p class="text-6xl font-black text-brand-teal-deep tabular-nums" x-text="phase === 'prep' ? fmt(prepLeft) : fmt(elapsed)">00:00</p>
                        <p class="text-sm text-zinc-500">
                            {{ __('dari maksimal :time', ['time' => sprintf('%02d:%02d', intdiv($max, 60), $max % 60)]) }}@if ($prep > 0) · {{ __('persiapan :n detik', ['n' => $prep]) }}@endif
                        </p>

                        <div class="flex h-14 items-center gap-1.5" aria-hidden="true">
                            <template x-for="(level, i) in levels" :key="i">
                                <span class="w-1.5 rounded-full transition-all duration-300" :class="phase === 'recording' ? 'bg-brand-teal' : 'bg-brand-teal-soft'" :style="`height: ${phase === 'recording' ? Math.min(56, level * 4) : 12 + (i % 3) * 6}px`"></span>
                            </template>
                        </div>

                        {{-- Live transcript (read only) --}}
                        <div
                            x-show="phase === 'recording' || phase === 'finishing' || phase === 'recorded' || phase === 'sending'"
                            x-cloak
                            class="max-h-40 w-full overflow-y-auto rounded-2xl border border-brand-teal/15 bg-brand-cream/50 p-4 text-start text-sm leading-relaxed text-zinc-800"
                            aria-live="polite"
                            data-test="live-transcript"
                        >
                            <span x-text="finalText"></span>
                            <span class="text-zinc-400" x-text="interimText"></span>
                            <span x-show="transcript === ''" class="text-zinc-400">{{ __('Teks jawabanmu muncul di sini saat kamu bicara...') }}</span>
                        </div>

                        <div class="flex flex-col items-center gap-3">
                            <template x-if="phase === 'idle'">
                                <button type="button" x-on:click="{{ $prep > 0 ? 'startPrep()' : 'record()' }}" class="flex size-24 items-center justify-center rounded-full bg-brand-teal text-white shadow-lift ring-8 ring-brand-teal-soft transition hover:scale-105" aria-label="{{ __('Mulai') }}" data-test="record-start">
                                    <x-icons.app-icon name="microphone" variant="s" class="size-10" />
                                </button>
                            </template>
                            <template x-if="phase === 'prep'">
                                <button type="button" x-on:click="skipPrep()" class="h-12 rounded-2xl bg-brand-amber px-6 font-bold text-brand-ink shadow-soft">{{ __('Mulai bicara sekarang') }}</button>
                            </template>
                            <template x-if="phase === 'recording'">
                                <button type="button" x-on:click="stop()" class="flex size-24 items-center justify-center rounded-full bg-red-600 text-white shadow-lift ring-8 ring-brand-rose-soft transition hover:scale-105" aria-label="{{ __('Berhenti') }}" data-test="record-stop">
                                    <span class="size-8 rounded-lg bg-white"></span>
                                </button>
                            </template>
                            <template x-if="(phase === 'recorded' || phase === 'sending') && blobUrl">
                                <audio controls :src="blobUrl" class="w-full max-w-sm"></audio>
                            </template>
                        </div>

                        <p class="text-sm text-zinc-500" x-show="phase === 'idle'">{{ $prep > 0 ? __('Tekan untuk mulai persiapan 1 menit, lalu rekaman berjalan otomatis.') : __('Tekan untuk mulai merekam. Jawab dalam bahasa Inggris semua pertanyaan di kiri.') }}</p>
                        <p class="text-sm text-zinc-500" x-show="phase === 'recording'" x-cloak>{{ __('Bicara jelas dalam bahasa Inggris. Tekan untuk berhenti.') }}</p>
                        <p class="text-sm font-semibold text-amber-700" x-show="phase === 'recorded' && transcript === ''" x-cloak>{{ __('Suaramu belum tertangkap. Coba rekam ulang dengan suara lebih jelas dan dekat ke mikrofon.') }}</p>

                        <div class="grid w-full grid-cols-2 gap-3" x-show="phase === 'recorded' || phase === 'sending'" x-cloak>
                            <button type="button" x-on:click="reset()" :disabled="phase === 'sending'" class="h-12 rounded-2xl bg-zinc-100 font-bold text-zinc-700 hover:bg-zinc-200 disabled:opacity-50">{{ __('Rekam ulang') }}</button>
                            <button type="button" x-on:click="send()" :disabled="phase === 'sending'" class="h-12 rounded-2xl bg-brand-teal font-bold text-white shadow-soft hover:bg-brand-teal-deep disabled:opacity-60" data-test="send-part">
                                <span x-show="phase !== 'sending'">{{ __('Kirim Part :n', ['n' => $prompt['part'] ?? $active + 1]) }}</span>
                                <span x-show="phase === 'sending'" x-cloak>{{ __('Mengirim...') }}</span>
                            </button>
                        </div>

                        <p class="text-sm font-semibold text-red-600" x-show="error" x-text="error" x-cloak></p>
                        @error('transcript')
                            <p class="text-sm font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </template>

                <p class="text-xs text-zinc-400">{{ __('Suaramu diubah menjadi teks oleh browser. Skular hanya menyimpan teksnya, bukan rekaman suaramu.') }}</p>
            </section>
        </div>
    @else
        <x-ui.empty-state icon="exclamation-triangle" :title="__('Soal Speaking belum tersedia')" :description="__('Hubungi tim Skular lewat menu Bantuan ya.')" class="mx-auto mt-10 max-w-xl" />
    @endif
</div>

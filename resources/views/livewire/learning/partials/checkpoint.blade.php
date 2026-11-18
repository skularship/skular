{{-- Final step: AI result label and the mandatory module feedback (score 1-5, hardest part, one open question, reason for a low score). "Kirim masukan & lanjut" stores the feedback and completes the module in one click. Only reachable once the checkpoint gate passes (enforced server side in ModulePlayer and ProgressService). --}}
@php
    $submitted_forms = $forms->filter(fn ($form) => $responses->has($form->id))->count();
    $score_label = \App\Enums\AiScoreLabel::tryFromScore($average_score);
    $is_low_score = $helpful_score !== null && $helpful_score <= $low_score_max;
    $input_classes = 'block w-full rounded-2xl border-2 border-zinc-300 bg-white px-4 py-3 text-base text-zinc-800 transition focus:border-brand-teal focus:outline-hidden focus:ring-2 focus:ring-brand-teal/30 dark:border-zinc-600 dark:bg-white/10 dark:text-zinc-200';
@endphp

<div class="sk-rise flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-brand-amber/25 px-2.5 py-1 text-xs font-bold text-brand-teal dark:text-brand-amber">
            <x-icons.app-icon name="flag" class="size-3.5" />
            {{ __('Checkpoint') }}
        </span>
        <h2 class="text-xl font-extrabold tracking-tight text-zinc-900 dark:text-white">{{ __('Kunci progresmu') }}</h2>
        <p class="max-w-2xl text-base text-zinc-600 dark:text-zinc-400">
            {{ __('Isi masukan singkat tentang modul ini. Setelah terkirim, modul ditandai selesai dan modul berikutnya terbuka.') }}
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat
            icon="beaker"
            tone="peach"
            :label="__('Latihan praktik')"
            :value="$submitted_forms.'/'.$forms->count()"
            :hint="__('form terkirim')"
        />

        <x-ui.stat
            icon="sparkles"
            tone="teal"
            :label="__('Hasil penilaian AI')"
            :value="$score_label?->label() ?? '—'"
            :hint="$score_label?->hint() ?? __('belum ada penilaian')"
        />

        <x-ui.stat
            icon="check-badge"
            :tone="$module_completed ? 'mint' : 'amber'"
            :label="__('Status checkpoint')"
            :value="$module_completed ? __('Selesai') : __('Belum dikunci')"
            :hint="$module_completed ? __('modul selesai') : __('setelah masukan terkirim')"
        />
    </div>

    @if ($module_completed)
        <div class="relative overflow-hidden rounded-4xl border border-emerald-200 bg-linear-to-br from-emerald-50 to-brand-amber/15 p-6 shadow-soft dark:border-emerald-500/30 dark:from-emerald-950/60 dark:to-brand-amber/5" data-test="module-completed">
            <div class="pointer-events-none absolute -top-16 -right-10 size-48 rounded-full bg-brand-amber/25 blur-3xl" aria-hidden="true"></div>

            <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <span class="sk-pop flex size-14 shrink-0 items-center justify-center rounded-2xl brand-gradient text-brand-ink shadow-pop">
                        <x-icons.app-icon name="trophy" class="size-7" />
                    </span>

                    <div>
                        <p class="text-lg font-extrabold text-emerald-700 dark:text-emerald-300">{{ __('Modul selesai, bagus sekali! 🎉') }}</p>
                        <p class="mt-1 text-base text-emerald-700/90 dark:text-emerald-300/90">
                            @if ($next_module)
                                {{ __('Modul ":title" sudah terbuka untukmu.', ['title' => $next_module->title]) }}
                            @else
                                {{ __('Ini modul terakhirmu. Lanjutkan ke pendaftaran beasiswamu ya!') }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if ($next_module && $next_module->isUnlocked())
                        <x-actions.button type="button" size="lg" iconTrailing="arrow-right" wire:navigate href="{{ route('modules.show', $next_module->id) }}" data-test="next-module">
                            {{ __('Lanjut modul berikutnya') }}
                        </x-actions.button>
                    @endif

                    <x-actions.link-button :href="route('dashboard')" wire:navigate variant="outline" icon="map">
                        {{ __('Lihat roadmap') }}
                    </x-actions.link-button>
                </div>
            </div>
        </div>
    @elseif ($checkpoint_block !== null)
        <x-ui.card variant="amber" class="flex flex-col gap-4">
            <x-feedback.callout variant="warning" :heading="__('Tahap akhir belum terbuka')" data-test="checkpoint-blocked">
                {{ $checkpoint_block_message }}
            </x-feedback.callout>

            <div class="flex flex-wrap gap-3">
                <x-actions.button type="button" icon="beaker" wire:click="goToSection('praktik')" loading="module.section">
                    @if ($checkpoint_block === 'unsaved')
                        {{ trans_choice('Kerjakan :count latihan dulu|Kerjakan :count latihan dulu', $practice_pending, ['count' => $practice_pending]) }}
                    @else
                        {{ __('Lihat status penilaian') }}
                    @endif
                </x-actions.button>
            </div>
        </x-ui.card>
    @endif

    @if ($module_completed || $checkpoint_block === null)
        <x-ui.card variant="teal" class="flex flex-col gap-7" data-test="module-feedback-form">
            <div class="flex items-start gap-3">
                <x-ui.icon-badge icon="chat-bubble-left-right" tone="teal" />
                <div>
                    <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Masukan singkat untuk modul ini') }}</p>
                    <p class="text-base text-zinc-600 dark:text-zinc-400">
                        {{ __('Jawab dengan jujur, tidak ada jawaban yang salah. Masukanmu langsung kami pakai untuk memperbaiki Skular.') }}
                    </p>
                </div>
            </div>

            {{-- 1. Helpfulness, five large buttons --}}
            <fieldset class="flex flex-col gap-3">
                <legend class="mb-3 text-base font-bold text-zinc-900 dark:text-white">
                    {{ __('1. Seberapa membantu modul ini untuk persiapan beasiswamu?') }}
                </legend>

                <div class="grid grid-cols-5 gap-2 sm:gap-3">
                    @foreach (range(1, 5) as $score)
                        <label class="flex min-h-14 cursor-pointer items-center justify-center rounded-2xl border-2 border-zinc-300 bg-white text-xl font-extrabold text-zinc-700 transition hover:border-brand-teal/60 has-[:checked]:border-brand-teal has-[:checked]:bg-brand-teal has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-teal/40 dark:border-zinc-600 dark:bg-zinc-900/50 dark:text-zinc-200">
                            <input type="radio" wire:model.live="helpful_score" value="{{ $score }}" class="sr-only" data-test="helpful-score-{{ $score }}">
                            {{ $score }}
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-between text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    <span>{{ __('Tidak membantu') }}</span>
                    <span>{{ __('Sangat membantu') }}</span>
                </div>

                @error('helpful_score')
                    <p class="text-base font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- 2. Hardest part, chips --}}
            <fieldset class="flex flex-col gap-3">
                <legend class="mb-3 text-base font-bold text-zinc-900 dark:text-white">
                    {{ __('2. Bagian mana yang paling menyulitkan?') }}
                </legend>

                <div class="flex flex-wrap gap-2 sm:gap-3">
                    @foreach ($difficulty_options as $value => $label)
                        <label class="flex min-h-12 cursor-pointer items-center rounded-full border-2 border-zinc-300 bg-white px-5 text-base font-semibold text-zinc-700 transition hover:border-brand-teal/60 has-[:checked]:border-brand-teal has-[:checked]:bg-brand-teal/10 has-[:checked]:text-brand-teal has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-teal/40 dark:border-zinc-600 dark:bg-zinc-900/50 dark:text-zinc-200">
                            <input type="radio" wire:model="difficulty" value="{{ $value }}" class="sr-only" data-test="difficulty-{{ $value }}">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                @error('difficulty')
                    <p class="text-base font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- 3. Rotating open question --}}
            <div class="flex flex-col gap-2">
                <label for="feedback-open-answer" class="text-base font-bold text-zinc-900 dark:text-white">
                    {{ __('3. :question', ['question' => $open_question]) }}
                </label>
                <textarea
                    id="feedback-open-answer"
                    wire:model="open_answer"
                    rows="3"
                    placeholder="{{ __('Tulis dengan kalimatmu sendiri, minimal :min karakter.', ['min' => $min_text_length]) }}"
                    class="{{ $input_classes }}"
                    data-test="open-answer"
                ></textarea>
                @error('open_answer')
                    <p class="text-base font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- 4. Only for a score of 1-3 --}}
            @if ($is_low_score)
                <div class="flex flex-col gap-2" data-test="low-score-reason-field">
                    <label for="feedback-low-score" class="text-base font-bold text-zinc-900 dark:text-white">
                        {{ __('4. Apa yang bikin kurang membantu?') }}
                    </label>
                    <textarea
                        id="feedback-low-score"
                        wire:model="low_score_reason"
                        rows="3"
                        placeholder="{{ __('Ceritakan sejujurnya, minimal :min karakter.', ['min' => $min_text_length]) }}"
                        class="{{ $input_classes }}"
                    ></textarea>
                    @error('low_score_reason')
                        <p class="text-base font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @error('checkpoint')
                <x-feedback.callout variant="warning" :heading="__('Modul belum bisa ditandai selesai')" data-test="checkpoint-blocked">
                    {{ $message }}
                </x-feedback.callout>
            @enderror

            @error('module_feedback')
                <p class="text-base font-medium text-amber-700" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex flex-col gap-3 border-t border-brand-teal/10 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                @unless ($module_completed)
                    <x-actions.button type="button" variant="outline" icon="arrow-left" wire:click="previousSection" loading="module.section">
                        {{ __('Kembali ke praktik') }}
                    </x-actions.button>
                @endunless

                <x-actions.button
                    type="button"
                    size="lg"
                    :icon="$module_completed ? 'paper-airplane' : 'check-badge'"
                    wire:click="submitFeedbackAndContinue"
                    loading="module.checkpoint"
                    class="w-full sm:ms-auto sm:w-auto"
                    data-test="submit-feedback-continue"
                >
                    {{ $module_completed ? __('Kirim masukan') : __('Kirim masukan & lanjut') }}
                </x-actions.button>
            </div>
        </x-ui.card>
    @endif
</div>

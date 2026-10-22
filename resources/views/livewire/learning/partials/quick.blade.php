@php
    $correct_count = 0;

    if ($quick_checked) {
        foreach ($questions as $question) {
            $given = $quick_answers[$question->id] ?? null;
            $correct_count += $question->isCorrect($given === null ? null : $given === '1') ? 1 : 0;
        }
    }

    $step_keys = array_keys($steps);
    $current_step = array_search($section, $step_keys, true);
    $next_step = $current_step === false ? null : ($step_keys[$current_step + 1] ?? null);
    $next_loading = $next_step === 'praktik' ? 'module.practice' : 'module.section';
@endphp

<div class="sk-rise flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-brand-amber/25 px-2.5 py-1 text-xs font-bold text-brand-teal dark:text-brand-amber">
            <x-icons.app-icon name="bolt" class="size-3.5" />
            {{ __('Quick Question') }}
        </span>
        <h2 class="text-xl font-extrabold tracking-tight text-zinc-900 dark:text-white">{{ __('Uji pemahamanmu') }}</h2>
        <p class="max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Pilih Benar atau Salah untuk setiap pernyataan. Jawabanmu langsung dikoreksi.') }}
        </p>
    </div>

    @if ($quick_checked)
        <div class="flex items-center gap-4 rounded-3xl border border-brand-teal/20 bg-linear-to-r from-brand-teal/8 to-brand-amber/10 px-5 py-4 dark:border-white/10">
            <x-ui.ring-progress :value="(int) round(($correct_count / max($questions->count(), 1)) * 100)" :size="72" tone="mint" />

            <div>
                <p class="text-sm font-bold text-zinc-900 dark:text-white">
                    {{ __(':correct dari :total benar', ['correct' => $correct_count, 'total' => $questions->count()]) }}
                </p>
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    {{ $correct_count === $questions->count() ? __('Sempurna! Kamu siap lanjut ke praktik. 🎉') : __('Baca lagi penjelasannya, lalu lanjut ke praktik.') }}
                </p>
            </div>
        </div>
    @endif

    @error('quick_answers')
        <x-feedback.callout variant="warning" :heading="__('Belum semua terjawab')">
            {{ $message }}
        </x-feedback.callout>
    @enderror

    <ol class="flex flex-col gap-4">
        @foreach ($questions as $question)
            @php
                $given = $quick_answers[$question->id] ?? null;
                $is_correct = $quick_checked ? $question->isCorrect($given === null ? null : $given === '1') : null;
            @endphp

            <li @class([
                'rounded-3xl border bg-white p-5 shadow-soft transition dark:bg-zinc-800/60',
                'border-emerald-300 dark:border-emerald-500/40' => $is_correct === true,
                'border-red-300 dark:border-red-500/40' => $is_correct === false,
                'border-brand-teal/12 dark:border-white/10' => $is_correct === null,
            ])>
                <div class="flex items-start gap-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full brand-gradient text-xs font-bold text-brand-ink">
                        {{ $loop->iteration }}
                    </span>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $question->question }}</p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 sm:ps-10">
                    @foreach (['1' => __('Benar'), '0' => __('Salah')] as $value => $label)
                        @php $selected = $given === $value; @endphp

                        <label @class([
                            'group flex cursor-pointer items-center gap-3 rounded-2xl border-2 px-4 py-3 text-sm font-semibold transition',
                            'border-brand-teal bg-brand-teal/8 text-brand-teal shadow-sm dark:bg-brand-teal/15 dark:text-brand-teal-light' => $selected,
                            'border-zinc-200 text-zinc-500 hover:border-brand-teal/50 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300' => ! $selected,
                        ])>
                            <input
                                type="radio"
                                wire:model="quick_answers[{{ $question->id }}]"
                                value="{{ $value }}"
                                class="size-4 border-zinc-300 text-brand-teal focus:ring-brand-teal"
                            >
                            <x-icons.app-icon :name="$value === '1' ? 'check-circle' : 'x-circle'" class="size-4" />
                            {{ $label }}

                            @if ($selected)
                                <x-icons.app-icon name="check-badge" class="ms-auto size-4" />
                            @endif
                        </label>
                    @endforeach
                </div>

                @if ($quick_checked)
                    <div
                        data-test="quick-result"
                        @class([
                            'mt-4 flex items-start gap-2 rounded-2xl border p-3.5 text-sm sm:ps-10',
                            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/50 dark:text-emerald-300' => $is_correct,
                            'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-950/50 dark:text-red-300' => ! $is_correct,
                        ])
                    >
                        <x-icons.app-icon :name="$is_correct ? 'check-circle' : 'x-circle'" class="mt-0.5 size-4 shrink-0" />

                        <span>
                            <strong>{{ $is_correct ? __('Benar!') : __('Belum tepat.') }}</strong>
                            @if (filled($question->explanation))
                                {{ $question->explanation }}
                            @endif
                        </span>
                    </div>
                @endif
            </li>
        @endforeach
    </ol>

    <x-ui.card class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count pernyataan|:count pernyataan', $questions->count()) }}
        </p>

        @if (! $quick_checked)
            <x-actions.button
                type="button"
                icon="check"
                wire:click="checkQuickAnswers"
                loading="module.quick"
                data-test="check-quick"
            >
                {{ __('Periksa jawaban') }}
            </x-actions.button>
        @else
            <x-actions.button
                type="button"
                iconTrailing="arrow-right"
                wire:click="nextSection"
                :loading="$next_loading"
                data-test="next-section"
            >
                {{ $next_step === 'praktik' ? __('Lanjut ke Praktik') : __('Lanjut') }}
            </x-actions.button>
        @endif
    </x-ui.card>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-actions.button type="button" variant="outline" icon="arrow-left" wire:click="previousSection" loading="module.section">
            {{ __('Kembali') }}
        </x-actions.button>
    </div>
</div>

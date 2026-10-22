<section
    class="overflow-hidden rounded-3xl border border-brand-amber/45 bg-brand-amber/10 shadow-soft dark:border-brand-amber/25 dark:bg-brand-amber/5"
    x-data="{ copied: false }"
    data-test="brainstorming"
>
    <button
        type="button"
        wire:click="toggle"
        class="flex w-full items-center gap-3 px-4 py-4 text-left transition hover:bg-brand-amber/10"
        data-test="brainstorm-toggle"
    >
        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl brand-gradient text-brand-ink shadow-pop">
            <x-icons.app-icon name="light-bulb" class="size-5" />
        </span>

        <span class="min-w-0 flex-1">
            <span class="block text-sm font-bold text-zinc-900 dark:text-white">
                {{ __('Butuh pemicu ide sebelum menulis?') }}
            </span>
            <span class="block text-xs text-zinc-600 dark:text-zinc-300">
                {{ __('Jawab pertanyaan pemancing, lalu minta AI menyusun kerangka draf dari jawabanmu.') }}
            </span>
        </span>

        <x-ui.pill variant="peach" class="hidden sm:inline-flex">{{ __('Pertanyaan pemancing') }}</x-ui.pill>

        <x-icons.app-icon name="chevron-down" class="size-5 text-brand-teal transition {{ $open ? 'rotate-180' : '' }}" />
    </button>

    @if ($open)
        <div class="border-t border-brand-amber/30 bg-white/50 px-4 py-5 dark:border-brand-amber/20 dark:bg-zinc-900/40">
            @if ($error)
                <x-feedback.callout variant="warning" :heading="__('Kerangka belum berhasil dibuat')" class="mb-4" data-test="brainstorm-error">
                    {{ $error }}

                    @if ($state !== 'loading' && $questions !== [])
                        <div class="mt-3">
                            <x-actions.button
                                type="button"
                                size="sm"
                                variant="outline"
                                icon="arrow-path"
                                wire:click="finish"
                                wire:loading.attr="disabled"
                                wire:target="finish"
                                data-test="brainstorm-retry"
                            >
                                {{ __('Coba lagi') }}
                            </x-actions.button>
                        </div>
                    @endif
                </x-feedback.callout>
            @endif

            @if ($state === 'empty')
                <x-ui.empty-state
                    icon="light-bulb"
                    :title="__('Pertanyaan pemancing belum tersedia')"
                    :description="__('Pengajar belum menambahkan pertanyaan untuk modul ini. Kamu tetap bisa mengisi latihan praktik di atas.')"
                    data-test="brainstorm-empty"
                />
            @elseif ($state === 'loading')
                <x-loading.ai loading="ai.brainstorm" :active="true" skeleton :skeleton-lines="3" data-test="brainstorm-loading" />
            @elseif ($state === 'summary' && is_array($outline))
                <div class="flex flex-col gap-4" data-test="brainstorm-summary">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-2xl brand-gradient-teal text-white shadow-soft">
                                <x-icons.app-icon name="sparkles" class="size-5" />
                            </span>
                            <div>
                                <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $outline['title'] ?? __('Kerangka draf jawaban') }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Dari jawaban brainstorming dan konteks modul ini.') }}</p>
                            </div>
                        </div>

                        <x-ui.pill variant="info" icon="sparkles">{{ __('Draf dari AI') }}</x-ui.pill>
                    </div>

                    <p class="inline-flex items-start gap-2 rounded-2xl border border-brand-teal/20 bg-brand-teal/5 px-4 py-3 text-xs text-brand-teal dark:border-brand-teal-light/20 dark:bg-brand-teal/10 dark:text-brand-teal-light">
                        <x-icons.app-icon name="information-circle" class="mt-0.5 size-3.5 shrink-0" />
                        {{ __('Ini kerangka, bukan jawaban final. Latihan praktikmu tidak berubah otomatis. Kembangkan dengan pengalamanmu sendiri ya.') }}
                    </p>

                    @if ($show_outline)
                        <div class="flex flex-col gap-4" data-test="brainstorm-outline">
                            <div class="rounded-2xl border border-brand-amber/30 bg-white px-4 py-3.5 dark:border-white/10 dark:bg-zinc-800">
                                <p class="text-xs font-bold tracking-wide text-brand-orange uppercase">{{ __('Inti gagasan') }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-zinc-700 dark:text-zinc-200">{{ $outline['core_idea'] ?? '' }}</p>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                @if (! empty($outline['points']))
                                    <div class="rounded-2xl border border-brand-teal/20 bg-white p-4 dark:border-white/10 dark:bg-zinc-800">
                                        <p class="text-sm font-bold text-brand-teal dark:text-brand-teal-light">{{ __('Poin yang dapat dikembangkan') }}</p>
                                        <ul class="mt-2 flex flex-col gap-2">
                                            @foreach ($outline['points'] as $point)
                                                <li class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                                    <x-icons.app-icon name="check" class="mt-0.5 size-3.5 shrink-0 text-brand-teal" />
                                                    <span>{{ $point }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if (! empty($outline['writing_flow']))
                                    <div class="rounded-2xl border border-brand-amber/30 bg-white p-4 dark:border-white/10 dark:bg-zinc-800">
                                        <p class="text-sm font-bold text-brand-orange">{{ __('Alur penulisan') }}</p>
                                        <ol class="mt-2 flex list-decimal flex-col gap-2 pl-5 text-sm text-zinc-700 dark:text-zinc-200">
                                            @foreach ($outline['writing_flow'] as $step)
                                                <li>{{ $step }}</li>
                                            @endforeach
                                        </ol>
                                    </div>
                                @endif
                            </div>

                            @if (! empty($outline['missing_information']))
                                <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-500/25 dark:bg-amber-950/40" data-test="brainstorm-missing">
                                    <p class="text-sm font-bold text-amber-700 dark:text-amber-300">{{ __('Informasi yang masih perlu kamu lengkapi') }}</p>
                                    <ul class="mt-2 flex flex-col gap-2">
                                        @foreach ($outline['missing_information'] as $item)
                                            <li class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                                <x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-3.5 shrink-0 text-amber-500" />
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (! empty($outline['reflection_questions']))
                                <div class="rounded-2xl border border-brand-teal/20 bg-white p-4 dark:border-white/10 dark:bg-zinc-800">
                                    <p class="text-sm font-bold text-brand-teal dark:text-brand-teal-light">{{ __('Pertanyaan refleksi lanjutan') }}</p>
                                    <ul class="mt-2 flex flex-col gap-2">
                                        @foreach ($outline['reflection_questions'] as $item)
                                            <li class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                                <x-icons.app-icon name="question-mark-circle" class="mt-0.5 size-3.5 shrink-0 text-brand-orange" />
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (! empty($outline['sections']))
                                <div class="flex flex-col gap-3">
                                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ __('Panduan per bagian latihan') }}</p>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        @foreach ($outline['sections'] as $section)
                                            <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                                                <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $section['field'] ?? '' }}</p>
                                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $section['guidance'] ?? '' }}</p>

                                                @if (! empty($section['points']))
                                                    <ul class="mt-2 flex flex-col gap-1.5">
                                                        @foreach ($section['points'] as $point)
                                                            <li class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                                                <x-icons.app-icon name="arrow-right" class="mt-0.5 size-3.5 shrink-0 text-brand-orange" />
                                                                <span>{{ $point }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2 border-t border-brand-amber/20 pt-4">
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText($refs.summary.innerText).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:border-brand-teal hover:text-brand-teal dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                            data-test="brainstorm-copy"
                        >
                            <x-icons.app-icon name="document-duplicate" class="size-4" />
                            <span x-text="copied ? '{{ __('Tersalin!') }}' : '{{ __('Salin kerangka') }}'"></span>
                        </button>

                        <x-actions.button type="button" variant="outline" icon="eye" wire:click="toggleOutline">
                            {{ $show_outline ? __('Sembunyikan kerangka') : __('Tampilkan kerangka') }}
                        </x-actions.button>

                        <x-actions.button type="button" variant="outline" icon="arrow-path" wire:click="restart">
                            {{ __('Mulai ulang') }}
                        </x-actions.button>
                    </div>

                    <span class="hidden" x-ref="summary" data-test="brainstorm-summary-text">{{ $summary }}</span>
                </div>
            @else
                <form wire:submit.prevent="finish" class="flex flex-col gap-5">
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                        {{ __('Pilih atau tulis jawabanmu. Kamu bisa mengubahnya sebelum meminta kerangka draf.') }}
                    </p>

                    @foreach ($questions as $index => $question)
                        @php
                            $question_id = $question['id'];
                            $type = $question['type'];
                            $is_multiple = $type === \App\Enums\BrainstormQuestionType::MultipleChoice->value;
                        @endphp

                        <fieldset class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800/60" data-test="brainstorm-question">
                            <legend class="flex flex-wrap items-center gap-2 px-1">
                                <span class="flex size-6 items-center justify-center rounded-full brand-gradient text-xs font-bold text-brand-ink">{{ $index + 1 }}</span>
                                <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ $question['question'] }}</span>

                                @if ($question['required'])
                                    <span class="rounded-full bg-brand-orange/10 px-2 py-0.5 text-[0.625rem] font-bold tracking-wide text-brand-orange uppercase">{{ __('Wajib') }}</span>
                                @endif

                                <span class="rounded-full bg-brand-teal/10 px-2 py-0.5 text-[0.625rem] font-semibold text-brand-teal dark:text-brand-teal-light">
                                    {{ $is_multiple ? __('Boleh lebih dari satu') : ($type === \App\Enums\BrainstormQuestionType::ShortText->value ? __('Tulis sendiri') : __('Pilih satu')) }}
                                </span>
                            </legend>

                            @if (filled($question['hint']))
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $question['hint'] }}</p>
                            @endif

                            @if ($type === \App\Enums\BrainstormQuestionType::ShortText->value)
                                <textarea
                                    wire:model.live.debounce.500ms="answers.{{ $question_id }}"
                                    rows="3"
                                    maxlength="{{ \App\Services\BrainstormingConfigService::MAX_TEXT_ANSWER_LENGTH }}"
                                    class="w-full rounded-2xl border-2 border-zinc-200 bg-white px-4 py-3 text-base text-zinc-700 outline-none transition focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/20 sm:text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                                    placeholder="{{ __('Tulis jawaban singkatmu di sini…') }}"
                                    data-test="brainstorm-text"
                                ></textarea>
                            @else
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($question['options'] as $option)
                                        <label class="block cursor-pointer">
                                            <input
                                                type="{{ $is_multiple ? 'checkbox' : 'radio' }}"
                                                name="brainstorm-{{ $question_id }}"
                                                value="{{ $option['id'] }}"
                                                wire:model.live.debounce.500ms="answers.{{ $question_id }}"
                                                class="peer sr-only"
                                            >
                                            <span @class([
                                                'flex items-start gap-2.5 rounded-2xl border-2 px-4 py-3 text-left text-sm font-medium transition',
                                                'border-zinc-200 bg-white text-zinc-600 hover:border-brand-teal/50 hover:text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                                'peer-checked:border-brand-teal peer-checked:bg-brand-teal/8 peer-checked:text-brand-teal peer-checked:shadow-sm dark:peer-checked:bg-brand-teal/15 dark:peer-checked:text-brand-teal-light',
                                                'peer-focus-visible:ring-2 peer-focus-visible:ring-brand-amber peer-focus-visible:ring-offset-2',
                                            ])>
                                                <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border-2 border-current">
                                                    <span class="size-2 rounded-full bg-current opacity-70"></span>
                                                </span>
                                                <span>{{ $option['label'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            @error('answers.'.$question_id)
                                <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    @endforeach

                    @error('answers')
                        <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-brand-amber/20 pt-4">
                        <p class="inline-flex max-w-md items-start gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                            <x-icons.app-icon name="information-circle" class="mt-0.5 size-3.5 shrink-0 text-brand-teal" />
                            {{ __('Jawabanmu tersimpan otomatis. AI hanya dipanggil saat kamu menekan tombol di samping.') }}
                        </p>

                        <x-actions.button
                            type="submit"
                            icon="sparkles"
                            loading="ai.brainstorm"
                            wire:loading.attr="disabled"
                            wire:target="finish"
                            data-test="brainstorm-finish"
                        >
                            {{ __('Susun kerangka draf') }}
                        </x-actions.button>
                    </div>

                    <x-loading.ai loading="ai.brainstorm" target="finish" skeleton :skeleton-lines="3" />
                </form>
            @endif
        </div>
    @endif
</section>

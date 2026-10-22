@php
    $submitted_forms = $forms->filter(fn ($form) => $responses->has($form->id))->count();
@endphp

<div class="sk-rise flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-brand-peach-soft px-2.5 py-1 text-xs font-bold text-brand-orange dark:bg-brand-orange/20 dark:text-brand-peach">
            <x-icons.app-icon name="beaker" class="size-3.5" />
            {{ __('Praktik') }}
        </span>
        <h2 class="text-xl font-extrabold tracking-tight text-zinc-900 dark:text-white">{{ __('Latihan praktik') }}</h2>
        <p class="max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Kerjakan sesuai materi yang sudah kamu baca. Setelah dikirim, AI akan menilai jawabanmu dan memberi saran perbaikan.') }}
        </p>

        @if ($forms->isNotEmpty())
            <div class="mt-1 flex items-center gap-3">
                <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                    {{ __(':done/:total latihan terkirim', ['done' => $submitted_forms, 'total' => $forms->count()]) }}
                </span>
                <x-ui.progress :value="(int) round(($submitted_forms / max($forms->count(), 1)) * 100)" :showValue="false" size="sm" class="w-40" />
            </div>
        @endif
    </div>

    @if (filled($module->introduction))
        @include('livewire.learning.partials.module-introduction', ['variant' => 'practice'])
    @endif

    @forelse ($forms as $form)
        @php
            $response = $responses->get($form->id);
            $schema = \App\Data\PracticeFieldData::collectFromSchema($form->fields ?? []);
        @endphp

        <x-ui.card padding="none" class="overflow-hidden" data-test="practice-form">
            <div class="flex flex-col gap-2 border-b border-brand-teal/10 bg-linear-to-r from-brand-teal/6 to-brand-amber/10 px-5 py-4 dark:border-white/10">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.icon-badge icon="beaker" tone="peach" size="sm" />
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ $form->title }}</h3>

                    @if ($response instanceof \App\Models\UserPracticeResponse)
                        <x-ui.pill :variant="$response->hasFeedback() ? 'success' : ($response->ai_status->value === 'failed' ? 'warning' : 'neutral')">
                            {{ $response->ai_status->label() }}
                        </x-ui.pill>

                        @if ($response->attempts > 1)
                            <x-ui.pill>{{ trans_choice('Percobaan :count|Percobaan :count', $response->attempts) }}</x-ui.pill>
                        @endif
                    @endif
                </div>

                @if (filled($form->description))
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $form->description }}</p>
                @endif
            </div>

            <div class="flex flex-col gap-5 p-5">
                @if (count($schema) === 0)
                    <x-ui.empty-state
                        icon="beaker"
                        :title="__('Latihan belum tersedia')"
                        :description="__('Admin belum menambahkan kolom untuk latihan ini.')"
                    />
                @else
                    <form wire:submit.prevent="submitPractice({{ $form->id }})" class="flex flex-col gap-5">
                        @foreach ($schema as $field)
                            <x-forms.dynamic-field
                                :field="$field"
                                :model="'answers.'.$form->id.'.'.$field->name"
                            />
                        @endforeach

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-brand-teal/10 pt-5 dark:border-white/10">
                            <p class="inline-flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                <x-icons.app-icon name="shield-check" class="size-3.5 text-brand-teal dark:text-brand-teal-light" />
                                {{ __('Jawabanmu disimpan aman dan hanya dipakai untuk evaluasi.') }}
                            </p>

                            <x-actions.button
                                type="submit"
                                icon="sparkles"
                                loading="ai.feedback_submit"
                                loading-target="submitPractice({{ $form->id }})"
                                data-test="submit-practice"
                            >
                                {{ $response instanceof \App\Models\UserPracticeResponse ? __('Submit ulang') : __('Submit Jawaban') }}
                            </x-actions.button>
                        </div>

                        <p
                            class="sr-only"
                            role="status"
                            aria-live="polite"
                            wire:loading
                            wire:target="submitPractice({{ $form->id }})"
                            data-test="practice-loading"
                        >
                            {{ __('Lagi nyimpen jawabanmu, lalu AI menilainya…') }}
                        </p>
                    </form>
                @endif

                @if ($response instanceof \App\Models\UserPracticeResponse && $response->ai_status->value !== 'pending')
                    @if ($response->hasFeedback())
                        @php
                            $feedback = is_array($response->ai_feedback) ? $response->ai_feedback : [];
                            $strengths = array_values(array_filter((array) ($feedback['strengths'] ?? [])));
                            $improvements = array_values(array_filter((array) ($feedback['improvements'] ?? [])));
                            $next_steps = array_values(array_filter((array) ($feedback['next_steps'] ?? [])));
                        @endphp

                        <section class="flex flex-col gap-5 rounded-3xl border border-brand-teal/20 bg-brand-teal/5 p-5 dark:border-brand-teal-light/20 dark:bg-brand-teal/10" data-test="ai-feedback">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    @if ($response->ai_score !== null)
                                        <x-ui.ring-progress :value="$response->ai_score" :size="84" :caption="__('Skor')" />
                                    @endif

                                    <div>
                                        <p class="text-xs font-bold tracking-wide text-brand-teal uppercase dark:text-brand-teal-light">{{ __('Feedback AI') }}</p>
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $response->submitted_at?->translatedFormat('d M Y, H:i') ?? '' }}
                                        </p>
                                    </div>
                                </div>

                                <x-ui.pill variant="info" icon="sparkles">{{ __('Dari evaluasi AI') }}</x-ui.pill>
                            </div>

                            <p class="text-sm leading-relaxed whitespace-pre-line text-zinc-700 dark:text-zinc-200">
                                {{ $feedback['feedback'] ?? '' }}
                            </p>

                            <div class="grid gap-4 lg:grid-cols-3">
                                @if ($strengths !== [])
                                    <div class="flex flex-col gap-2 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 dark:border-emerald-500/25 dark:bg-emerald-950/40">
                                        <p class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-700 dark:text-emerald-300">
                                            <x-icons.app-icon name="check-circle" class="size-4" />
                                            {{ __('Kekuatanmu') }}
                                        </p>
                                        <ul class="flex flex-col gap-2">
                                            @foreach ($strengths as $item)
                                                <li class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                                    <x-icons.app-icon name="check" class="mt-0.5 size-3.5 shrink-0 text-emerald-500" />
                                                    <span>{{ $item }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($improvements !== [])
                                    <div class="flex flex-col gap-2 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-500/25 dark:bg-amber-950/40">
                                        <p class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 dark:text-amber-300">
                                            <x-icons.app-icon name="arrow-path" class="size-4" />
                                            {{ __('Yang perlu diperbaiki') }}
                                        </p>
                                        <ul class="flex flex-col gap-2">
                                            @foreach ($improvements as $item)
                                                <li class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                                    <x-icons.app-icon name="arrow-right" class="mt-0.5 size-3.5 shrink-0 text-amber-500" />
                                                    <span>{{ $item }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($next_steps !== [])
                                    <div class="flex flex-col gap-2 rounded-2xl border border-brand-teal/25 bg-white/80 p-4 dark:border-brand-teal-light/20 dark:bg-zinc-900/50">
                                        <p class="inline-flex items-center gap-1.5 text-sm font-bold text-brand-teal dark:text-brand-teal-light">
                                            <x-icons.app-icon name="flag" class="size-4" />
                                            {{ __('Langkah berikutnya') }}
                                        </p>
                                        <ul class="flex flex-col gap-2">
                                            @foreach ($next_steps as $item)
                                                <li class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                                    <x-icons.app-icon name="arrow-right" class="mt-0.5 size-3.5 shrink-0 text-brand-orange" />
                                                    <span>{{ $item }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </section>
                    @elseif ($response->ai_status->value === 'failed')
                        <x-feedback.callout variant="warning" :heading="__('Feedback AI belum tersedia')" data-test="ai-failed">
                            {{ filled($response->ai_error) ? $response->ai_error : __('Layanan AI sedang tidak bisa dihubungi. Jawabanmu tetap tersimpan, coba lagi nanti.') }}

                            <div class="mt-3">
                                <x-actions.button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    icon="arrow-path"
                                    wire:click="retryFeedback({{ $form->id }})"
                                    loading="ai.feedback_retry"
                                    data-test="retry-feedback"
                                >
                                    {{ __('Coba lagi') }}
                                </x-actions.button>
                            </div>
                        </x-feedback.callout>
                    @else
                        <x-loading.ai loading="ai.feedback" :active="true" skeleton :skeleton-lines="2" data-test="feedback-pending" />
                    @endif
                @endif

                @php
                    $has_writable_field = collect($schema)->contains(
                        fn ($field): bool => in_array($field->type, [\App\Enums\PracticeFieldType::Text, \App\Enums\PracticeFieldType::Textarea], true),
                    );
                @endphp

                @if ($has_writable_field)
                    <livewire:learning.brainstorming
                        :module="$module"
                        :form_id="$form->id"
                        :key="'brainstorm-'.$form->id"
                    />
                @endif
            </div>
        </x-ui.card>
    @empty
        <x-ui.empty-state
            icon="beaker"
            :title="__('Belum ada latihan praktik')"
            :description="__('Admin belum menambahkan latihan untuk modul ini. Kamu bisa langsung menuju checkpoint.')"
        >
            <x-actions.button type="button" iconTrailing="arrow-right" wire:click="nextSection" loading="module.section">
                {{ __('Ke checkpoint') }}
            </x-actions.button>
        </x-ui.empty-state>
    @endforelse

    @if ($forms->isNotEmpty())
        <livewire:learning.mentor-chat
            :module="$module"
            :form_id="$forms->first()->id"
        />
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-actions.button type="button" variant="outline" icon="arrow-left" wire:click="previousSection" loading="module.section">
            {{ __('Kembali') }}
        </x-actions.button>

        <x-actions.button type="button" iconTrailing="arrow-right" wire:click="nextSection" loading="module.section" data-test="next-section">
            {{ __('Lanjut ke checkpoint') }}
        </x-actions.button>
    </div>
</div>

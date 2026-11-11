{{-- Praktik step: practice forms (dynamic fields), in-request grading status (submit and "Nilai ulang" wait for the AI), AI result label and feedback, re-assessment button, brainstorming, AI Mentor chat, and the gated "Lanjut ke checkpoint" button. --}}
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

    @if (filled($gate_notice) && $checkpoint_block !== null)
        <x-feedback.callout variant="warning" :heading="__('Tahap akhir belum terbuka')" data-test="checkpoint-gate-notice">
            {{ $gate_notice }}
        </x-feedback.callout>
    @endif

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
                        <x-ui.pill :variant="$response->hasFeedback() ? 'success' : ($response->canRetryEvaluation() ? 'warning' : 'info')" data-test="evaluation-status">
                            @if ($response->hasFeedback())
                                {{ __('Sudah dinilai') }}
                            @elseif ($response->canRetryEvaluation())
                                {{ __('Penilaian gagal') }}
                            @else
                                {{ __('Sedang dinilai AI') }}
                            @endif
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
                        :contained="false"
                        size="sm"
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

                        {{-- The AI grades inside this request, so the mentee sees how long to wait while the button stays disabled. --}}
                        <x-loading.inline
                            loading="ai.feedback_grading"
                            :detail="__(config('loading.detail')['ai.feedback_grading'])"
                            target="submitPractice({{ $form->id }})"
                            :delay="false"
                            class="justify-end text-sm"
                            data-test="practice-loading"
                        />
                    </form>
                @endif

                @if ($response instanceof \App\Models\UserPracticeResponse && $response->ai_status->value !== 'pending')
                    @if ($response->hasFeedback())
                        @php
                            // Rows hold either the {point, explanation} shape or the legacy
                            // plain strings; FeedbackPointData reads both without rewriting data.
                            $feedback = is_array($response->ai_feedback) ? $response->ai_feedback : [];
                            $strengths = \App\Data\FeedbackPointData::listFrom($feedback['strengths'] ?? []);
                            $improvements = \App\Data\FeedbackPointData::listFrom($feedback['improvements'] ?? []);
                            $next_steps = \App\Data\FeedbackPointData::listFrom($feedback['next_steps'] ?? []);
                            $summary_paragraphs = array_values(array_filter(
                                preg_split('/\R{2,}/u', trim(is_string($feedback['feedback'] ?? null) ? $feedback['feedback'] : '')) ?: [],
                                fn (string $paragraph): bool => trim($paragraph) !== '',
                            ));
                        @endphp

                        <section class="flex flex-col gap-5 rounded-3xl border border-brand-teal/20 bg-brand-teal/5 p-5 dark:border-brand-teal-light/20 dark:bg-brand-teal/10" data-test="ai-feedback">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                @php $score_label = \App\Enums\AiScoreLabel::tryFromScore($response->ai_score); @endphp

                                <div class="flex flex-col gap-1.5">
                                    <p class="text-xs font-bold tracking-wide text-brand-teal uppercase">{{ __('Feedback AI') }}</p>

                                    @if ($score_label !== null)
                                        <div class="flex flex-wrap items-center gap-2" data-test="ai-score-label">
                                            <x-ui.pill :variant="$score_label->variant()" icon="star" class="text-sm">{{ $score_label->label() }}</x-ui.pill>
                                            <span class="text-sm text-zinc-600">{{ $score_label->hint() }}</span>
                                        </div>
                                    @endif

                                    <p class="text-xs text-zinc-500">
                                        {{ $response->submitted_at?->translatedFormat('d M Y, H:i') ?? '' }}
                                    </p>
                                </div>

                                <x-ui.pill variant="info" icon="sparkles">{{ __('Dari evaluasi AI') }}</x-ui.pill>
                            </div>

                            @if ($summary_paragraphs !== [])
                                <div class="flex max-w-prose flex-col gap-3 text-[0.9375rem] leading-relaxed text-zinc-700" data-test="ai-feedback-summary">
                                    @foreach ($summary_paragraphs as $paragraph)
                                        <p class="whitespace-pre-line">{{ trim($paragraph) }}</p>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Stacked full-width cards: a bold point plus its explanation needs more width than three columns give. --}}
                            <div class="flex flex-col gap-4">
                                @if ($strengths !== [])
                                    @include('livewire.learning.partials.feedback-points', ['items' => $strengths, 'title' => __('Kekuatanmu'), 'icon' => 'check-circle', 'marker' => 'check', 'tone' => 'emerald', 'test' => 'ai-feedback-strengths'])
                                @endif

                                @if ($improvements !== [])
                                    @include('livewire.learning.partials.feedback-points', ['items' => $improvements, 'title' => __('Yang perlu diperbaiki'), 'icon' => 'arrow-path', 'marker' => 'arrow-right', 'tone' => 'amber', 'test' => 'ai-feedback-improvements'])
                                @endif

                                @if ($next_steps !== [])
                                    @include('livewire.learning.partials.feedback-points', ['items' => $next_steps, 'title' => __('Langkah berikutnya'), 'icon' => 'flag', 'marker' => 'arrow-right', 'tone' => 'teal', 'test' => 'ai-feedback-next-steps'])
                                @endif
                            </div>
                        </section>
                    @elseif ($response->canRetryEvaluation())
                        <x-feedback.callout variant="warning" :heading="__('Feedback AI belum tersedia')" data-test="ai-failed">
                            @if ($response->ai_status->value === 'failed' && filled($response->ai_error))
                                {{ $response->ai_error }}
                            @elseif ($response->ai_status->value === 'failed')
                                {{ __('Layanan AI sedang tidak bisa dihubungi. Jawabanmu tetap tersimpan, coba lagi nanti.') }}
                            @else
                                {{ __('Penilaian AI berhenti sebelum selesai. Jawabanmu tetap tersimpan, nilai ulang ya.') }}
                            @endif

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
                                    {{ __('Nilai ulang') }}
                                </x-actions.button>

                                <x-loading.inline
                                    loading="ai.feedback_grading"
                                    :detail="__(config('loading.detail')['ai.feedback_retry'])"
                                    target="retryFeedback({{ $form->id }})"
                                    :delay="false"
                                    class="mt-2 text-sm"
                                    data-test="retry-loading"
                                />
                            </div>
                        </x-feedback.callout>
                    @else
                        <div class="flex flex-col gap-2" role="status" aria-live="polite">
                            <p class="text-sm font-semibold text-brand-teal">{{ __('Jawabanmu sedang dinilai AI. Halaman ini diperbarui otomatis.') }}</p>
                            <p class="text-sm text-zinc-600" data-test="feedback-pending-estimate">{{ __('Biasanya selesai dalam 10–30 detik. Kamu boleh lanjut membaca, hasilnya muncul sendiri di sini.') }}</p>
                            <x-loading.ai loading="ai.feedback" :active="true" skeleton :skeleton-lines="2" data-test="feedback-pending" />
                        </div>
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
            data-test="practice-empty"
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

        <div class="flex flex-col items-end gap-1.5">
            <x-actions.button
                type="button"
                iconTrailing="arrow-right"
                wire:click="nextSection"
                loading="module.section"
                :disabled="$checkpoint_block !== null"
                aria-describedby="checkpoint-gate-status"
                data-test="next-section"
            >
                {{ __('Lanjut ke checkpoint') }}
            </x-actions.button>

            @if ($checkpoint_block_message !== null)
                <p id="checkpoint-gate-status" class="max-w-xs text-end text-xs font-medium text-zinc-500" data-test="checkpoint-gate-status">
                    {{ $checkpoint_block_message }}
                </p>
            @endif
        </div>
    </div>
</div>

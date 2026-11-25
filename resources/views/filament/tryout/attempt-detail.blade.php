{{-- Admin detail of a Try Out attempt (TryoutAttemptInfolist ViewEntry): one block per subtest with raw score, band,
     level, AI status/error, and each answer (key and correctness for L/R, essay for Writing, browser-made transcript
     for Speaking, AI criteria). Data: $getRecord() (TryoutAttempt). --}}
@php
    /** @var \App\Models\TryoutAttempt $attempt */
    $attempt = $getRecord();
    $attempt->loadMissing(['sections.answers.question']);
    $band = fn ($value): string => $value === null ? '-' : number_format((float) $value, 1, ',', '');
@endphp

<div class="flex flex-col gap-6">
    @foreach ($attempt->sections as $section)
        <section class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <header class="flex flex-wrap items-center gap-3">
                <h3 class="text-base font-bold">{{ $section->skill->getLabel() }}</h3>
                <x-filament::badge :color="$section->status->getColor()">{{ $section->status->getLabel() }}</x-filament::badge>
                <span class="text-sm">Band: <strong>{{ $band($section->band) }}</strong></span>
                @if ($section->raw_score !== null)
                    <span class="text-sm">Skor mentah: {{ $section->raw_score }}/{{ $section->max_score }}</span>
                    <span class="text-sm">Level akhir: {{ $section->final_level ?? '-' }}</span>
                @endif
                @if ($section->ai_status)
                    <x-filament::badge :color="$section->ai_status->getColor()">AI: {{ $section->ai_status->getLabel() }}</x-filament::badge>
                @endif
            </header>

            @if ($section->ai_error)
                <p class="mt-2 text-sm text-danger-600">{{ $section->ai_error }}</p>
            @endif

            @if (is_array($section->ai_assessment))
                <ul class="mt-2 text-sm">
                    @foreach ($section->ai_assessment['criteria'] ?? [] as $criterion)
                        <li>{{ $criterion['label'] ?? '' }}: <strong>{{ $band($criterion['band'] ?? null) }}</strong> · {{ $criterion['reason'] ?? '' }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 pe-3">#</th>
                            <th class="py-2 pe-3">Kode</th>
                            <th class="py-2 pe-3">Level</th>
                            <th class="py-2 pe-3">Jawaban</th>
                            <th class="py-2 pe-3">Kunci / hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($section->answers as $answer)
                            <tr class="border-b border-gray-100 align-top dark:border-white/5">
                                <td class="py-2 pe-3">{{ $answer->sequence }}</td>
                                <td class="py-2 pe-3 font-mono text-xs">{{ $answer->question->code }}</td>
                                <td class="py-2 pe-3">{{ $answer->level_at_answer }}</td>
                                <td class="py-2 pe-3">
                                    @if ($section->skill === \App\Enums\TryoutSkill::Speaking)
                                        <p class="whitespace-pre-line">{{ $answer->transcript ?: '-' }}</p>
                                        <p class="text-xs text-gray-500">Transkrip dari browser mentee{{ $answer->time_spent_seconds !== null ? ' · '.$answer->time_spent_seconds.' detik bicara' : '' }}{{ $answer->word_count !== null ? ' · '.$answer->word_count.' kata' : '' }}</p>
                                    @else
                                        <p class="whitespace-pre-line">{{ \Illuminate\Support\Str::limit((string) $answer->response, 1500) ?: '-' }}</p>
                                        @if ($answer->word_count !== null && $section->skill === \App\Enums\TryoutSkill::Writing)
                                            <p class="text-xs text-gray-500">{{ $answer->word_count }} kata</p>
                                        @endif
                                    @endif
                                </td>
                                <td class="py-2 pe-3">
                                    @if ($section->skill->isObjective())
                                        {{ $answer->question->answer_key }}
                                        @if ($answer->answered_at)
                                            <x-filament::badge :color="$answer->is_correct ? 'success' : 'danger'">{{ $answer->is_correct ? 'Benar' : 'Salah' }}</x-filament::badge>
                                        @endif
                                    @else
                                        Band {{ $band($answer->band) }}
                                        @if ($answer->ai_status)
                                            <x-filament::badge :color="$answer->ai_status->getColor()">{{ $answer->ai_status->getLabel() }}</x-filament::badge>
                                        @endif
                                        @if ($answer->ai_error)
                                            <p class="text-xs text-danger-600">{{ $answer->ai_error }}</p>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</div>

{{-- Admin "Ringkasan Feedback Modul" widget (App\Filament\Widgets\ModuleFeedbackSummary): totals, hardest parts, helpfulness 1-5, modules needing attention, and latest low-score reasons. Inline styles because the panel has no custom Tailwind theme. --}}
@php
    use App\Enums\FeedbackDifficulty;

    $filterUrl = fn (string $filter, string|int $value): string => $list_url.'?'.http_build_query(['filters' => [$filter => ['value' => $value]]]);
    $maxScoreCount = max(1, ...array_values($summary->score_distribution));
    $card = 'padding: 0.875rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(127, 127, 127, 0.2);';
    $muted = 'opacity: 0.7; font-size: 0.8125rem;';
    $bigNumber = 'font-size: 1.5rem; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.2;';
    $track = 'height: 0.5rem; border-radius: 9999px; background: rgba(127, 127, 127, 0.15);';
    $scoreColor = fn (float|int $score): string => $score >= 4 ? 'success' : ($score >= 3 ? 'warning' : 'danger');
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        heading="Ringkasan Feedback Modul"
        description="Masukan wajib mentee di akhir modul: seberapa membantu (1-5), bagian yang paling menyulitkan, dan alasan saat skor rendah."
    >
        <x-slot name="afterHeader">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="module" aria-label="Modul">
                        <option value="">Semua modul</option>
                        @foreach ($modules as $id => $title)
                            <option value="{{ $id }}">{{ \Illuminate\Support\Str::limit($title, 40) }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="days" aria-label="Rentang waktu">
                        <option value="7">7 hari</option>
                        <option value="30">30 hari</option>
                        <option value="90">90 hari</option>
                        <option value="0">Semua waktu</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </x-slot>

        <div data-test="module-feedback-summary" style="display: grid; gap: 1.25rem;">
            @if ($summary->total === 0)
                <p style="{{ $muted }} padding: 1rem 0; text-align: center;">
                    Belum ada feedback modul pada rentang ini.
                </p>
            @else
                {{-- Headline numbers --}}
                <div style="display: grid; gap: 0.75rem; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));">
                    <div style="{{ $card }}">
                        <div style="{{ $muted }}">Feedback masuk</div>
                        <div style="{{ $bigNumber }}">{{ $summary->total }}</div>
                        <div style="{{ $muted }}">dari {{ $summary->mentees }} mentee</div>
                    </div>
                    <div style="{{ $card }}">
                        <div style="{{ $muted }}">Rata-rata membantu</div>
                        <div style="{{ $bigNumber }}">{{ number_format((float) $summary->average_score, 1, ',', '.') }}<span style="font-size: 0.875rem; font-weight: 500; opacity: 0.6;"> / 5</span></div>
                        <div style="{{ $muted }}">
                            {{ match (true) { $summary->average_score >= 4 => 'Modul dirasa membantu', $summary->average_score >= 3 => 'Cukup, masih bisa ditingkatkan', default => 'Perlu perhatian' } }}
                        </div>
                    </div>
                    <div style="{{ $card }}">
                        <div style="{{ $muted }}">Skor rendah (1-3)</div>
                        <div style="{{ $bigNumber }}">{{ $summary->low_score_count }} <span style="font-size: 0.875rem; font-weight: 500; opacity: 0.6;">({{ $summary->lowScorePercentage() }}%)</span></div>
                        <div style="{{ $muted }}">Wajib menulis alasan</div>
                    </div>
                    @php
                        $hardest = collect($summary->difficulties)->first(fn (array $row): bool => $row['difficulty'] !== FeedbackDifficulty::TidakAda && $row['count'] > 0);
                    @endphp
                    <div style="{{ $card }}">
                        <div style="{{ $muted }}">Paling menyulitkan</div>
                        <div style="{{ $bigNumber }}">{{ $hardest ? $hardest['difficulty']->getLabel() : '—' }}</div>
                        <div style="{{ $muted }}">{{ $hardest ? $hardest['count'].' mentee ('.$hardest['percentage'].'%)' : 'Belum ada bagian yang dikeluhkan' }}</div>
                    </div>
                </div>

                <div style="display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));">
                    {{-- Hardest parts --}}
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Bagian yang paling menyulitkan</div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;" data-test="feedback-difficulties">
                            <tbody>
                                @foreach ($summary->difficulties as $row)
                                    @php $isNone = $row['difficulty'] === FeedbackDifficulty::TidakAda; @endphp
                                    <tr style="border-top: 1px solid rgba(127, 127, 127, 0.2);">
                                        <td style="padding: 0.4rem 0.25rem; white-space: nowrap;">
                                            <x-filament::link :href="$filterUrl('difficulty', $row['difficulty']->value)" color="gray" size="sm">
                                                {{ $row['difficulty']->getLabel() }}
                                            </x-filament::link>
                                        </td>
                                        <td style="padding: 0.4rem 0.25rem; text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                                            {{ $row['count'] }} mentee
                                        </td>
                                        <td style="padding: 0.4rem 0.25rem; text-align: right; font-variant-numeric: tabular-nums; opacity: 0.7; width: 3rem;">
                                            {{ $row['percentage'] }}%
                                        </td>
                                        <td style="padding: 0.4rem 0.25rem; width: 40%;">
                                            <div style="{{ $track }}">
                                                <div style="height: 100%; width: {{ $row['percentage'] }}%; border-radius: 9999px; background: {{ $isNone ? 'rgb(148, 163, 184)' : 'rgb(234, 88, 12)' }};"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Helpfulness distribution --}}
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Seberapa membantu (1-5)</div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;" data-test="feedback-scores">
                            <tbody>
                                @foreach (array_reverse($summary->score_distribution, true) as $score => $count)
                                    <tr style="border-top: 1px solid rgba(127, 127, 127, 0.2);">
                                        <td style="padding: 0.4rem 0.25rem; white-space: nowrap;">
                                            <x-filament::link :href="$filterUrl('helpful_score', $score)" color="gray" size="sm">
                                                Skor {{ $score }}
                                            </x-filament::link>
                                        </td>
                                        <td style="padding: 0.4rem 0.25rem; text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">
                                            {{ $count }} mentee
                                        </td>
                                        <td style="padding: 0.4rem 0.25rem; width: 50%;">
                                            <div style="{{ $track }}">
                                                <div style="height: 100%; width: {{ (int) round($count / $maxScoreCount * 100) }}%; border-radius: 9999px; background: {{ $score >= 4 ? 'rgb(13, 148, 136)' : ($score === 3 ? 'rgb(217, 119, 6)' : 'rgb(220, 38, 38)') }};"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Modules needing attention --}}
                @if (count($summary->modules) > 1)
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Modul yang paling perlu perhatian</div>
                        <div style="{{ $muted }} margin-bottom: 0.5rem;">Diurutkan dari rata-rata skor membantu terendah.</div>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;" data-test="feedback-modules">
                                <thead>
                                    <tr style="text-align: left; opacity: 0.7;">
                                        <th style="padding: 0.5rem 0.25rem;">Modul</th>
                                        <th style="padding: 0.5rem 0.25rem; text-align: right;">Feedback</th>
                                        <th style="padding: 0.5rem 0.25rem; text-align: right;">Rata-rata</th>
                                        <th style="padding: 0.5rem 0.25rem; text-align: right;">Skor 1-3</th>
                                        <th style="padding: 0.5rem 0.25rem;">Paling menyulitkan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($summary->modules as $row)
                                        <tr style="border-top: 1px solid rgba(127, 127, 127, 0.2);">
                                            <td style="padding: 0.5rem 0.25rem; font-weight: 600;">{{ $row['title'] }}</td>
                                            <td style="padding: 0.5rem 0.25rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['total'] }}</td>
                                            <td style="padding: 0.5rem 0.25rem; text-align: right;">
                                                <x-filament::badge :color="$scoreColor($row['average_score'])">
                                                    {{ number_format($row['average_score'], 1, ',', '.') }}
                                                </x-filament::badge>
                                            </td>
                                            <td style="padding: 0.5rem 0.25rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['low_score_count'] }}</td>
                                            <td style="padding: 0.5rem 0.25rem;">{{ $row['top_difficulty']?->getLabel() ?? 'Tidak ada' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Latest low-score reasons --}}
                <div>
                    <div style="font-weight: 600; margin-bottom: 0.5rem;">Alasan skor rendah terbaru</div>
                    @forelse ($summary->recent_low_scores as $item)
                        <div style="{{ $card }} margin-bottom: 0.5rem;" data-test="feedback-low-score">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-bottom: 0.375rem;">
                                <x-filament::badge :color="$scoreColor($item['score'])">Skor {{ $item['score'] }}</x-filament::badge>
                                <span style="font-weight: 600;">{{ $item['module'] }}</span>
                                <span style="{{ $muted }}">· {{ $item['mentee'] }} · {{ $item['sent_at'] }}</span>
                                @if ($item['difficulty'] && $item['difficulty'] !== FeedbackDifficulty::TidakAda)
                                    <x-filament::badge color="gray">Sulit: {{ $item['difficulty']->getLabel() }}</x-filament::badge>
                                @endif
                            </div>
                            @if ($item['reason'])
                                <div style="font-size: 0.875rem;">“{{ \Illuminate\Support\Str::limit($item['reason'], 240) }}”</div>
                            @endif
                            @if ($item['open_answer'])
                                <div style="{{ $muted }} margin-top: 0.25rem;">Jawaban terbuka: {{ \Illuminate\Support\Str::limit($item['open_answer'], 160) }}</div>
                            @endif
                        </div>
                    @empty
                        <p style="{{ $muted }}">Tidak ada feedback dengan skor rendah pada rentang ini.</p>
                    @endforelse
                </div>
            @endif

            <div>
                <x-filament::link :href="$list_url" icon="heroicon-m-arrow-right" icon-position="after" size="sm">
                    Lihat semua feedback
                </x-filament::link>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

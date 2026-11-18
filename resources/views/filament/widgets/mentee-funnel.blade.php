{{-- Admin "Funnel Mentee" widget (App\Filament\Widgets\MenteeFunnel): steps of mentees registered in the last 7/30 days, with count and share continued. Inline styles because the panel has no custom Tailwind theme. --}}
<x-filament-widgets::widget>
    <x-filament::section
        heading="Funnel Mentee"
        description="Mentee yang mendaftar dalam rentang ini, dan berapa yang mencapai tiap langkah (dihitung per mentee)."
    >
        <x-slot name="afterHeader">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="days" aria-label="Rentang waktu">
                    <option value="7">7 hari</option>
                    <option value="30">30 hari</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        @php
            $first = $steps[0]->count ?? 0;
        @endphp

        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;" data-test="mentee-funnel">
            <thead>
                <tr style="text-align: left; opacity: 0.7;">
                    <th style="padding: 0.5rem 0.25rem;">Langkah</th>
                    <th style="padding: 0.5rem 0.25rem; text-align: right;">Mentee</th>
                    <th style="padding: 0.5rem 0.25rem; text-align: right;">Lanjut dari langkah sebelumnya</th>
                    <th style="padding: 0.5rem 0.25rem; width: 35%;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($steps as $step)
                    @php
                        $width = $first > 0 ? (int) round($step->count / $first * 100) : 0;
                    @endphp
                    <tr style="border-top: 1px solid rgba(127, 127, 127, 0.2);">
                        <td style="padding: 0.5rem 0.25rem; font-weight: 600;">{{ $loop->iteration }}. {{ $step->step->label() }}</td>
                        <td style="padding: 0.5rem 0.25rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $step->count }}</td>
                        <td style="padding: 0.5rem 0.25rem; text-align: right; font-variant-numeric: tabular-nums;">
                            {{ $step->continued_percentage === null ? '—' : $step->continued_percentage.'%' }}
                        </td>
                        <td style="padding: 0.5rem 0.25rem;">
                            <div style="height: 0.5rem; border-radius: 9999px; background: rgba(127, 127, 127, 0.15);">
                                <div style="height: 100%; width: {{ $width }}%; border-radius: 9999px; background: rgb(13, 148, 136);"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::section>
</x-filament-widgets::widget>

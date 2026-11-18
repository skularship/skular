{{-- Admin Google Analytics top-10 list (App\Filament\Widgets\Analytics\AnalyticsBreakdownList): pages, sources, devices or countries with a relative bar, or the reason the report failed. Inline styles because the panel has no custom Tailwind theme. --}}
<x-filament-widgets::widget>
    <x-filament::section :heading="$type->heading()">
        @if ($error !== null)
            <p style="font-size: 0.875rem; color: rgb(185, 28, 28);" data-test="analytics-error">{{ $error }}</p>
        @elseif ($rows === [])
            <p style="font-size: 0.875rem; opacity: 0.7;">Belum ada data untuk periode ini.</p>
        @else
            <ul style="display: flex; flex-direction: column; gap: 0.625rem; font-size: 0.875rem;" data-test="analytics-{{ $type->value }}">
                @foreach ($rows as $row)
                    <li>
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                            <span style="flex-shrink: 0; font-variant-numeric: tabular-nums; opacity: 0.8;">
                                {{ number_format($row['value'], 0, ',', '.') }} {{ $type->unit() }}
                            </span>
                        </div>
                        <div style="margin-top: 0.25rem; height: 0.375rem; border-radius: 9999px; background: rgba(127, 127, 127, 0.15);">
                            <div style="height: 100%; width: {{ $row['percentage'] }}%; border-radius: 9999px; background: #f2994a;"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

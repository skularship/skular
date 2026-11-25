{{-- Try Out pool health widget (App\Filament\Widgets\TryoutPoolHealth): published questions per skill x level,
     red below the minimum per level. Data: matrix, min, labels. --}}
<x-filament-widgets::widget>
    <x-filament::section heading="Kesehatan bank soal Try Out" :description="'Jumlah soal terbit per level. Merah bila kurang dari '.$min.' (batas minimal adaptif).'">
        <div class="overflow-x-auto">
            <table class="w-full text-center text-sm">
                <thead>
                    <tr>
                        <th class="py-2 text-left">Subtes</th>
                        @for ($level = 1; $level <= 5; $level++)
                            <th class="py-2">Level {{ $level }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach ($matrix as $skill => $levels)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-2 text-left font-semibold">{{ $labels[$skill] }}</td>
                            @foreach ($levels as $count)
                                <td class="py-2">
                                    <span @class([
                                        'inline-flex min-w-10 justify-center rounded-md px-2 py-1 font-bold',
                                        'bg-danger-50 text-danger-700 dark:bg-danger-500/15 dark:text-danger-300' => $count < $min,
                                        'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-300' => $count >= $min,
                                    ])>{{ $count }}</span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

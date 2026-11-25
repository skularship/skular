{{-- Data table of a Listening attachment or a Writing Task 1 prompt (<x-tryout.data-table>).
     Props: table (array{columns?: list<string>, rows?: list<list<string>>}). Numbers are right aligned. --}}
@props([
    'table' => null,
])

@if (is_array($table) && ! empty($table['columns']))
    <div {{ $attributes->class(['overflow-x-auto rounded-2xl border border-brand-teal/15']) }}>
        <table class="w-full text-sm">
            <thead class="bg-brand-teal-soft text-zinc-900">
                <tr>
                    @foreach ($table['columns'] as $index => $column)
                        <th class="px-4 py-3 font-bold {{ $index === 0 ? 'text-start' : 'text-end' }}">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white">
                @foreach ($table['rows'] ?? [] as $row)
                    <tr>
                        @foreach ((array) $row as $index => $cell)
                            <td class="px-4 py-2.5 text-zinc-700 {{ $index === 0 ? 'text-start' : 'text-end tabular-nums' }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

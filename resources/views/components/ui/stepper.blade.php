@props([
    'steps' => [],
    'current' => 1,
])

<ol class="flex flex-wrap items-center gap-2" aria-label="{{ __('Progres langkah') }}">
    @foreach ($steps as $index => $label)
        @php
            $number = $index + 1;
            $isCompleted = $number < $current;
            $isCurrent = $number === $current;
        @endphp

        <li>
            <span @class([
                'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium transition',
                'border-transparent brand-gradient text-brand-ink' => $isCompleted,
                'border-brand-teal bg-white text-brand-teal dark:bg-zinc-800 dark:text-brand-teal-light' => $isCurrent,
                'border-zinc-200 text-zinc-400 dark:border-zinc-700 dark:text-zinc-500' => ! $isCompleted && ! $isCurrent,
            ])>
                @if ($isCompleted)
                    <x-icons.app-icon name="check" class="size-3.5" />
                @else
                    <span @class([
                        'flex size-4 items-center justify-center rounded-full text-[0.625rem] font-semibold',
                        'bg-brand-teal text-white' => $isCurrent,
                        'bg-zinc-200 text-zinc-500 dark:bg-white/10 dark:text-zinc-400' => ! $isCurrent,
                    ])>{{ $number }}</span>
                @endif

                <span class="hidden sm:inline">{{ $label }}</span>
            </span>
        </li>
    @endforeach
</ol>

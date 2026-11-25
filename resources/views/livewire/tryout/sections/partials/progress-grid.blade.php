{{-- Question number panel of Listening/Reading: progress only, never a jump link (answers lock, D9).
     Data: answered, sequence (current), total, columns. --}}
<div class="rounded-3xl border border-brand-teal/10 bg-white p-5 shadow-soft" data-test="progress-grid">
    <p class="text-sm font-extrabold text-zinc-900">{{ __('Progres soal') }}</p>
    <ol class="mt-3 grid gap-1.5" style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));">
        @for ($n = 1; $n <= $total; $n++)
            <li @class([
                'flex aspect-square items-center justify-center rounded-lg text-[0.7rem] font-bold',
                'bg-brand-teal text-white' => $n <= $answered,
                'border-2 border-brand-orange bg-white text-zinc-900' => $n === $sequence && $n > $answered,
                'bg-zinc-100 text-zinc-400' => $n > $answered && $n !== $sequence,
            ])>{{ $n }}</li>
        @endfor
    </ol>
    <div class="mt-3 flex gap-4 text-xs text-zinc-500">
        <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-brand-teal"></span>{{ __('Terjawab') }}</span>
        <span class="flex items-center gap-1.5"><span class="size-3 rounded border-2 border-brand-orange"></span>{{ __('Sekarang') }}</span>
    </div>
</div>

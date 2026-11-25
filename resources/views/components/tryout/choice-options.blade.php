{{-- Large option buttons with a letter bubble (<x-tryout.choice-options>) bound to the Livewire "answer" property.
     Used for single choice, map letters, headings and matching. Props: options (letter => text), model, compact. --}}
@props([
    'options' => [],
    'model' => 'answer',
    'compact' => false,
])

<div class="flex flex-col gap-3" role="radiogroup" x-data="{ picked: $wire.entangle(@js($model)) }">
    @foreach ($options as $letter => $text)
        <button
            type="button"
            role="radio"
            x-on:click="picked = @js((string) $letter)"
            :aria-checked="picked === @js((string) $letter) ? 'true' : 'false'"
            :class="picked === @js((string) $letter)
                ? 'border-brand-teal bg-brand-teal-soft ring-2 ring-brand-teal/20'
                : 'border-zinc-200 bg-white hover:border-brand-teal/50 hover:bg-brand-cream'"
            class="group flex w-full items-center gap-4 rounded-2xl border-2 px-4 text-start transition {{ $compact ? 'py-3' : 'py-4' }}"
            data-test="option-{{ $letter }}"
        >
            <span
                :class="picked === @js((string) $letter) ? 'bg-brand-teal text-white' : 'bg-zinc-100 text-zinc-600 group-hover:bg-brand-teal-soft'"
                class="flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-extrabold transition"
            >{{ $letter }}</span>
            <span class="text-base font-medium text-zinc-900">{{ $text }}</span>
        </button>
    @endforeach
</div>

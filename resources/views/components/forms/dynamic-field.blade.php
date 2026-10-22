@props([
    'field',
    'model',
])

@php
    /** @var \App\Data\PracticeFieldData $field */
    $fieldId = 'field-'.str_replace(['.', ' '], '-', $model);
    $hasError = $errors->has($model);
    $errorMessage = $errors->first($model);
    $helpText = $field->help_text;
    $type = $field->type->value;
    $selected = data_get($this, $model);
@endphp

<div class="w-full" data-field="{{ $field->name }}">
    <div class="mb-1.5 flex flex-wrap items-baseline gap-1.5">
        <label for="{{ $fieldId }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $field->label }}
        </label>

        @if ($field->required)
            <span class="text-xs font-medium text-red-500">{{ __('wajib') }}</span>
        @endif
    </div>

    @if ($type === 'textarea')
        <div x-data="{ count: {{ mb_strlen((string) ($selected ?? '')) }} }">
            <textarea
                id="{{ $fieldId }}"
                wire:model="{{ $model }}"
                rows="6"
                placeholder="{{ $field->placeholder }}"
                x-on:input="count = $event.target.value.length"
                @class([
                    'block w-full rounded-xl border bg-white px-3.5 py-3 text-base leading-relaxed shadow-none transition sm:text-sm',
                    'text-zinc-700 placeholder:text-zinc-400 dark:bg-white/10 dark:text-zinc-300 dark:placeholder:text-zinc-400',
                    'focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-accent focus:ring-offset-accent-foreground',
                    'border-red-500 focus:ring-red-500 dark:border-red-500/60' => $hasError,
                    'border-zinc-200 dark:border-zinc-600' => ! $hasError,
                ])
            ></textarea>

            <p class="mt-1 text-end text-[0.6875rem] font-medium text-zinc-400 dark:text-zinc-500">
                <span x-text="count">{{ mb_strlen((string) ($selected ?? '')) }}</span> {{ __('karakter') }}
            </p>
        </div>

    @elseif ($type === 'number')
        <x-forms.input
            id="{{ $fieldId }}"
            wire:model="{{ $model }}"
            type="number"
            step="any"
            :placeholder="$field->placeholder"
        />

    @elseif ($type === 'date')
        <x-forms.input
            id="{{ $fieldId }}"
            wire:model="{{ $model }}"
            type="date"
        />

    @elseif ($type === 'url')
        <x-forms.input
            id="{{ $fieldId }}"
            wire:model="{{ $model }}"
            type="url"
            placeholder="{{ $field->placeholder ?? 'https://...' }}"
        />

    @elseif ($type === 'select')
        <x-forms.select id="{{ $fieldId }}" wire:model="{{ $model }}">
            <option value="">{{ $field->placeholder ?? __('Pilih salah satu') }}</option>
            @foreach ($field->options as $option)
                <option value="{{ $option }}" @selected($selected === $option)>{{ $option }}</option>
            @endforeach
        </x-forms.select>

    @elseif ($type === 'radio')
        <div class="flex flex-col gap-2" id="{{ $fieldId }}">
            @foreach ($field->options as $option)
                <label @class([
                    'flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-2.5 text-sm font-medium transition',
                    'border-brand-teal bg-brand-teal/5 text-brand-teal dark:text-brand-teal-light' => $selected === $option,
                    'border-zinc-200 text-zinc-600 hover:border-zinc-300 dark:border-zinc-700 dark:text-zinc-300' => $selected !== $option,
                ])>
                    <input type="radio" wire:model="{{ $model }}" value="{{ $option }}" class="size-4 border-zinc-300 text-brand-teal focus:ring-brand-teal">
                    {{ $option }}
                </label>
            @endforeach
        </div>

    @elseif ($type === 'checkbox')
        <div class="flex flex-col gap-2" id="{{ $fieldId }}">
            @foreach ($field->options as $option)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 dark:border-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model="{{ $model }}" value="{{ $option }}" class="size-4 rounded border-zinc-300 text-brand-teal focus:ring-brand-teal">
                    {{ $option }}
                </label>
            @endforeach
        </div>

    @elseif ($type === 'file')
        <div class="flex flex-col gap-2">
            <input
                id="{{ $fieldId }}"
                type="file"
                wire:model="{{ $model }}"
                class="block w-full cursor-pointer rounded-lg border border-dashed border-zinc-300 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-700 hover:border-brand-teal dark:border-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200"
            >
            <x-loading.inline loading="upload" :target="$model" tone="teal" />
        </div>

    @else
        <x-forms.input
            id="{{ $fieldId }}"
            wire:model="{{ $model }}"
            type="text"
            :placeholder="$field->placeholder"
        />
    @endif

    @if ($hasError)
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $errorMessage }}</p>
    @elseif (filled($helpText))
        <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $helpText }}</p>
    @endif
</div>

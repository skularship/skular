@props([
    'length' => 6,
    'name' => null,
    'model' => null,
    'label' => null,
])

@php
    $valueExpression = filled($model)
        ? '$wire.entangle(\''.$model.'\')'
        : "''";

    $lastIndex = $length - 1;
@endphp

<div
    x-data="{
        value: {{ $valueExpression }},
        digits: Array.from({ length: {{ $length }} }, () => ''),
        init() {
            this.digits = this.toDigits(this.value);

            this.$watch('value', (value) => {
                if ((value || '') !== this.digits.join('')) {
                    this.digits = this.toDigits(value);
                }
            });
        },
        toDigits(value) {
            return Array.from({ length: {{ $length }} }, (_, index) => (value || '')[index] || '');
        },
        sync() {
            this.value = this.digits.join('');
        },
        focusBox(index) {
            this.$refs.boxes?.querySelectorAll('input')[index]?.focus();
        },
        handleInput(index, event) {
            const digit = event.target.value.replace(/\D/g, '').slice(-1);

            this.digits[index] = digit;
            this.sync();

            if (digit && index < {{ $lastIndex }}) {
                this.focusBox(index + 1);
            }
        },
        handleKeydown(index, event) {
            if (event.key === 'Backspace') {
                if (this.digits[index]) {
                    this.digits[index] = '';
                    this.sync();
                } else if (index > 0) {
                    event.preventDefault();
                    this.digits[index - 1] = '';
                    this.sync();
                    this.focusBox(index - 1);
                }
            } else if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                this.focusBox(index - 1);
            } else if (event.key === 'ArrowRight' && index < {{ $lastIndex }}) {
                event.preventDefault();
                this.focusBox(index + 1);
            }
        },
        handlePaste(event) {
            const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, {{ $length }});

            if (! pasted) {
                return;
            }

            event.preventDefault();

            this.digits = this.toDigits(pasted);
            this.sync();
            this.focusBox(Math.min(pasted.length, {{ $lastIndex }}));
        },
    }"
    x-on:otp:reset.window="value = ''; digits = toDigits('')"
    {{ $attributes->class(['flex flex-col items-center gap-4']) }}
>
    @if (filled($label))
        <label class="sr-only">{{ $label }}</label>
    @endif

    {{-- Six fixed 44px boxes need 304px, which does not fit a 320px phone next
         to card padding. The boxes share the available width instead and only
         cap at 44px once there is room. --}}
    <div x-ref="boxes" x-on:paste.stop.prevent="handlePaste($event)" class="flex w-full max-w-[19rem] items-center justify-center gap-1.5 sm:gap-2">
        @for ($index = 0; $index < $length; $index++)
            <input
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                autocomplete="{{ $index === 0 ? 'one-time-code' : 'off' }}"
                aria-label="{{ filled($label) ? $label : __('Character :position', ['position' => $index + 1]) }}"
                x-model="digits[{{ $index }}]"
                x-on:input="handleInput({{ $index }}, $event)"
                x-on:keydown="handleKeydown({{ $index }}, $event)"
                class="h-12 min-w-0 max-w-11 flex-1 rounded-lg border border-zinc-200 bg-white text-center text-lg font-medium text-zinc-800 transition focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-accent focus:ring-offset-accent-foreground dark:border-zinc-600 dark:bg-white/10 dark:text-white"
            />
        @endfor
    </div>

    @if (filled($name))
        <input type="hidden" name="{{ $name }}" x-model="value" />
    @endif
</div>

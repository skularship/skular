{{--
    Product tour overlay (coach marks): dims the page, spotlights the step target and shows a card with
    Kembali / Lanjut / Lewati. Logic lives in resources/js/product-tour.js (Alpine "productTour").
    Props: key (tour key), steps (list of {target, title, body}), autoStart, persist ("server" calls $wire.complete(), "local" uses localStorage for guests).
    Accessible: role="dialog", focus moves to the card, Esc skips, arrow keys step, motion respects prefers-reduced-motion.
--}}
@props([
    'key',
    'steps' => [],
    'autoStart' => false,
    'persist' => 'server',
])

<div
    x-data="productTour(@js(['key' => $key, 'steps' => $steps, 'autoStart' => (bool) $autoStart, 'persist' => $persist]))"
    x-on:keydown.escape.window="open && skip()"
    x-on:keydown.right.window="open && next()"
    x-on:keydown.left.window="open && prev()"
    data-tour-overlay="{{ $key }}"
>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="sk-tour" data-test="product-tour">
            {{-- Dimmed backdrop (or the spotlight box-shadow when a target exists) and a click blocker; clicking outside never skips, so a stray tap cannot dismiss the tour for good. --}}
            <div class="sk-tour__backdrop" x-show="! rect" aria-hidden="true"></div>
            <div class="sk-tour__spotlight" x-show="rect" x-bind:style="spotlightStyle()" aria-hidden="true"></div>
            <div class="sk-tour__blocker" aria-hidden="true"></div>

            <div
                x-ref="card"
                role="dialog"
                aria-modal="true"
                x-bind:aria-labelledby="'sk-tour-title-' + key"
                x-bind:aria-describedby="'sk-tour-body-' + key"
                tabindex="-1"
                class="sk-tour__card"
                x-bind:class="{ 'sk-tour__card--centered': ! rect }"
                x-bind:style="cardStyle()"
            >
                <div class="flex items-center justify-between gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-amber/25 px-2.5 py-1 text-[0.6875rem] font-bold text-brand-teal">
                        <x-icons.app-icon name="sparkles" class="size-3.5" />
                        {{ __('Panduan') }} <span x-text="(index + 1) + '/' + steps.length"></span>
                    </span>

                    <button type="button" x-on:click="skip()" class="inline-flex min-h-9 items-center rounded-lg px-2 text-xs font-semibold text-zinc-500 transition hover:text-zinc-800 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-teal" data-test="tour-skip">
                        {{ __('Lewati') }}
                    </button>
                </div>

                <h2 x-bind:id="'sk-tour-title-' + key" class="mt-3 text-base font-extrabold tracking-tight text-zinc-900" x-text="step ? step.title : ''"></h2>
                <p x-bind:id="'sk-tour-body-' + key" class="mt-1.5 text-sm leading-relaxed text-zinc-600" x-text="step ? step.body : ''"></p>

                {{-- Progress dots --}}
                <div class="mt-4 flex items-center gap-1.5" aria-hidden="true">
                    <template x-for="(item, dot) in steps" x-bind:key="dot">
                        <span class="h-1.5 rounded-full transition-all" x-bind:class="dot === index ? 'w-5 bg-brand-teal' : 'w-1.5 bg-zinc-200'"></span>
                    </template>
                </div>

                <div class="mt-4 flex items-center justify-between gap-2">
                    <button
                        type="button"
                        x-on:click="prev()"
                        x-bind:disabled="index === 0"
                        class="inline-flex min-h-10 items-center gap-1 rounded-xl px-3 text-sm font-semibold text-zinc-600 transition hover:text-brand-teal disabled:invisible focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-teal"
                    >
                        <x-icons.app-icon name="arrow-left" class="size-4" />
                        {{ __('Kembali') }}
                    </button>

                    <button
                        type="button"
                        x-on:click="next()"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-xl bg-brand-teal px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-teal-deep focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-teal focus-visible:ring-offset-2"
                        data-test="tour-next"
                    >
                        <span x-text="isLast ? @js(__('Selesai')) : @js(__('Lanjut'))"></span>
                        <x-icons.app-icon name="arrow-right" class="size-4" x-show="! isLast" />
                        <x-icons.app-icon name="check" class="size-4" x-show="isLast" />
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

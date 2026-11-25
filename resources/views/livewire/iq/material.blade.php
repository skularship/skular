{{-- Tes IQ material (App\Livewire\Iq\Material). Paid access: the slides (PPT exported to PDF) in the in-page viewer
     (Alpine "tryoutPdfViewer" from resources/js/pdf-viewer.js: canvas pages, watermark, no download, no print). Others:
     a locked frame with the call to open access. Data: test (IqTest|null), has_pdf, unlocked, premium_enabled, watermark. --}}
<div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <a href="{{ route('iq.index') }}" wire:navigate class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-zinc-500 hover:text-brand-teal">
        <x-icons.app-icon name="arrow-left" class="size-4" />
        {{ __('Tes IQ Latihan') }}
    </a>

    <x-ui.section-heading :eyebrow="__('Materi')" eyebrow-icon="presentation-chart-bar" level="1" :title="__('Materi Tes IQ')" :description="__('Strategi mengerjakan soal Numerik, Visuospasial, Verbal, Logika, dan Teks.')" />

    @if (! $has_pdf)
        <x-ui.empty-state icon="presentation-chart-bar" :title="__('Materi sedang disiapkan')" :description="__('Tim Skular sedang menyiapkan materi Tes IQ. Cek lagi nanti ya.')" data-test="iq-material-empty" />
    @elseif ($unlocked)
        <section class="overflow-hidden rounded-4xl border border-brand-teal/10 bg-white shadow-lift" data-test="iq-material">
            <header class="flex items-center gap-3 bg-brand-teal-deep px-5 py-4 text-white">
                <span class="flex size-10 items-center justify-center rounded-2xl bg-white/15"><x-icons.app-icon name="presentation-chart-bar" class="size-5 text-brand-amber" /></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-extrabold">{{ $test->materialTitle() }}</p>
                    <p class="text-xs text-white/75">{{ __('Hanya bisa dibaca di Skular') }}</p>
                </div>
            </header>

            <div
                wire:ignore
                x-data="tryoutPdfViewer({ url: @js(route('iq.material.pdf')), watermark: @js($watermark) })"
                x-on:contextmenu.prevent
                x-on:dragstart.prevent
                class="select-none print:hidden"
                data-test="iq-material-viewer"
            >
                <div class="flex flex-wrap items-center gap-2 border-b border-brand-teal/10 bg-brand-cream/70 px-4 py-2.5 text-sm" x-show="status === 'ready'" x-cloak>
                    <button type="button" x-on:click="goTo(current - 1)" class="flex size-9 items-center justify-center rounded-xl bg-white text-zinc-700 shadow-soft hover:text-brand-teal" aria-label="{{ __('Halaman sebelumnya') }}"><x-icons.app-icon name="chevron-up" class="size-4" /></button>
                    <button type="button" x-on:click="goTo(current + 1)" class="flex size-9 items-center justify-center rounded-xl bg-white text-zinc-700 shadow-soft hover:text-brand-teal" aria-label="{{ __('Halaman berikutnya') }}"><x-icons.app-icon name="chevron-down" class="size-4" /></button>
                    <span class="font-semibold text-zinc-700 tabular-nums">{{ __('Halaman') }} <span x-text="current"></span> / <span x-text="pages"></span></span>
                    <span class="ms-auto flex items-center gap-2">
                        <button type="button" x-on:click="setZoom(-0.2)" class="flex size-9 items-center justify-center rounded-xl bg-white text-zinc-700 shadow-soft hover:text-brand-teal" aria-label="{{ __('Perkecil') }}"><x-icons.app-icon name="minus" class="size-4" /></button>
                        <span class="w-12 text-center font-semibold text-zinc-700 tabular-nums" x-text="Math.round(zoom * 100) + '%'"></span>
                        <button type="button" x-on:click="setZoom(0.2)" class="flex size-9 items-center justify-center rounded-xl bg-white text-zinc-700 shadow-soft hover:text-brand-teal" aria-label="{{ __('Perbesar') }}"><x-icons.app-icon name="plus" class="size-4" /></button>
                    </span>
                </div>

                <div x-ref="scroller" class="h-[75vh] overflow-auto bg-zinc-100/80 px-3 py-4 sm:px-6">
                    <template x-if="status === 'loading'">
                        <div class="mx-auto flex max-w-3xl flex-col gap-4">
                            <div class="aspect-[1/1.414] w-full animate-pulse rounded-lg bg-white shadow-soft"></div>
                        </div>
                    </template>
                    <template x-if="status === 'error'">
                        <div class="mx-auto mt-10 max-w-md rounded-3xl bg-white p-6 text-center text-sm text-zinc-600 shadow-soft" x-text="error"></div>
                    </template>
                    <div class="mx-auto flex max-w-5xl flex-col gap-4" x-show="status === 'ready'">
                        <template x-for="number in pages" :key="number">
                            <div :data-page="number" class="relative mx-auto min-h-[40vh] w-full">
                                <div class="mx-auto aspect-[1/1.414] w-full max-w-3xl animate-pulse rounded-lg bg-white/70"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <p class="flex items-center gap-2 border-t border-brand-teal/10 px-5 py-3 text-xs text-zinc-500">
                    <x-icons.app-icon name="shield-check" class="size-4 text-brand-teal" />
                    {{ __('Materi ini khusus untukmu. Setiap halaman diberi tanda nama dan emailmu, dan tidak bisa diunduh atau dicetak.') }}
                </p>
            </div>
            <p class="hidden p-6 text-sm text-zinc-600 print:block">{{ __('Materi ini hanya bisa dibaca di website Skular.') }}</p>
        </section>
    @else
        <x-ui.card variant="peach" padding="lg" class="flex flex-col items-center gap-4 text-center" data-test="iq-material-locked">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-white text-brand-orange shadow-soft"><x-icons.app-icon name="lock-closed" class="size-7" /></span>
            <h2 class="text-xl font-extrabold text-zinc-900">{{ __('Materi terbuka setelah kamu membuka akses') }}</h2>
            <p class="max-w-lg text-sm text-zinc-600">{{ __('Satu akses membuka skor, pembahasan, materi, dan mentor AI Tes IQ untuk akunmu.') }}</p>
            @if ($premium_enabled)
                <x-actions.link-button :href="route('premium')" variant="primary" size="lg" icon="lock-open">{{ __('Buka akses') }}</x-actions.link-button>
            @endif
        </x-ui.card>
    @endif

    <p class="text-center text-xs text-zinc-500">{{ config('iq.disclaimer') }}</p>
</div>

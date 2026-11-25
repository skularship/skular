{{-- Try Out theory (App\Livewire\Tryout\Theory, D11). Pro: theory module text (rendered with <x-learning.rich-content>)
     and the theory PDF read in an in-page viewer (resources/js/pdf-viewer.js: canvas pages, watermark, no download,
     no print). Gratis: both shown as locked frames with the Pro CTA. The paid material link shows for everyone.
     Data: tryout, can_theory, module (Module|null, Pro only), has_module, has_pdf, premium_enabled, watermark. --}}
<div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <a href="{{ route('tryout.show', $tryout) }}" wire:navigate class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-zinc-500 hover:text-brand-teal">
        <x-icons.app-icon name="arrow-left" class="size-4" />
        {{ $tryout->title }}
    </a>

    <x-ui.section-heading :eyebrow="__('Teori IELTS')" eyebrow-icon="book-open" level="1" :title="__('Pelajari teori sebelum mencoba lagi')" :description="__('Strategi per tipe soal: TRUE/FALSE/NOT GIVEN, completion, Task 1, Part 2, dan lainnya.')" />

    @if ($has_pdf)
        <section id="pdf-teori" class="scroll-mt-6 overflow-hidden rounded-4xl border border-brand-teal/10 bg-white shadow-lift" data-test="theory-pdf">
            <header class="flex flex-wrap items-center gap-3 bg-brand-teal-deep px-5 py-4 text-white">
                <span class="flex size-10 items-center justify-center rounded-2xl bg-white/15"><x-icons.app-icon name="document-text" class="size-5 text-brand-amber" /></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-extrabold">{{ $tryout->theoryPdfTitle() }}</p>
                    <p class="text-xs text-white/75">
                        {{ __('Hanya bisa dibaca di Skular') }}@if ($tryout->theory_pdf_updated_at) · {{ __('Diperbarui :date', ['date' => $tryout->theory_pdf_updated_at->setTimezone('Asia/Jakarta')->translatedFormat('j M Y')]) }}@endif
                    </p>
                </div>
                @unless ($can_theory)
                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-orange px-2.5 py-1 text-xs font-extrabold"><x-icons.app-icon name="lock-closed" class="size-3.5" /> PRO</span>
                @endunless
            </header>

            @if ($can_theory)
                <div
                    wire:ignore
                    x-data="tryoutPdfViewer({ url: @js(route('tryout.theory.pdf', $tryout)), watermark: @js($watermark) })"
                    x-on:contextmenu.prevent
                    x-on:dragstart.prevent
                    class="select-none print:hidden"
                    data-test="theory-pdf-viewer"
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
                                <div :data-page="number" class="relative mx-auto min-h-[60vh] w-full">
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
                <p class="hidden p-6 text-sm text-zinc-600 print:block">{{ __('Teori ini hanya bisa dibaca di website Skular.') }}</p>
            @else
                <div class="relative h-80 overflow-hidden bg-zinc-100/80" data-test="theory-pdf-locked">
                    <div class="pointer-events-none absolute inset-x-6 top-6 flex flex-col gap-3 blur-[3px]" aria-hidden="true">
                        @foreach ([90, 75, 85, 60, 80, 70, 88, 55] as $width)
                            <span class="h-3 rounded-full bg-zinc-300" style="width: {{ $width }}%"></span>
                        @endforeach
                    </div>
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-white/60 p-6 text-center backdrop-blur-[2px]">
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-white text-brand-orange shadow-soft"><x-icons.app-icon name="lock-closed" class="size-6" /></span>
                        <p class="font-extrabold text-zinc-900">{{ __('PDF teori bisa dibaca dengan paket Pro') }}</p>
                        @if ($premium_enabled)
                            <x-actions.link-button :href="route('premium')" variant="primary" icon="star">{{ __('Lihat paket Pro') }}</x-actions.link-button>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    @endif

    <div class="grid gap-3 sm:grid-cols-2">
        @if ($tryout->hasMaterialsLink())
            <button type="button" wire:click="openMaterials" class="flex items-center gap-4 rounded-3xl bg-brand-amber p-5 text-start shadow-soft transition hover:brightness-105" data-test="materials-link">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-white/60 text-brand-ink"><x-icons.app-icon name="shopping-bag" class="size-6" /></span>
                <span class="font-extrabold text-brand-ink">{{ $tryout->materialsLabel() }}</span>
            </button>
        @endif
    </div>

    @if (! $can_theory)
        <x-ui.card variant="peach" padding="lg" class="flex flex-col items-center gap-4 text-center" data-test="theory-locked">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-white text-brand-orange shadow-soft"><x-icons.app-icon name="lock-closed" class="size-7" /></span>
            <h2 class="text-xl font-extrabold text-zinc-900">{{ __('Teori IELTS tersedia untuk paket Pro') }}</h2>
            <p class="max-w-lg text-sm text-zinc-600">{{ __('Baca teori per tipe soal dan PDF teorinya langsung di website, lalu tanya mentor AI tentang soal yang kamu kerjakan.') }}</p>
            @if ($premium_enabled)
                <x-actions.link-button :href="route('premium')" variant="primary" size="lg" icon="star">{{ __('Lihat paket Pro') }}</x-actions.link-button>
            @endif
        </x-ui.card>
    @elseif ($module)
        <article class="rounded-4xl border border-brand-teal/10 bg-white p-6 shadow-soft sm:p-10" data-test="theory-module">
            <h2 class="text-2xl font-extrabold text-zinc-900">{{ $module->title }}</h2>
            @if (filled($module->summary_material))
                <div class="mt-3 text-zinc-700"><x-learning.rich-content :content="$module->summary_material" /></div>
            @endif
            @foreach ($module->coreSections as $section)
                <section class="mt-8">
                    <h3 class="text-lg font-extrabold text-brand-teal-deep">{{ $section->subtitle }}</h3>
                    <div class="mt-2"><x-learning.rich-content :content="$section->body" :format="$section->body_format->value" /></div>
                </section>
            @endforeach
        </article>
    @elseif (! $has_module && ! $has_pdf)
        <x-ui.empty-state icon="book-open" :title="__('Teori sedang disiapkan')" :description="__('Tim Skular sedang menulis teori untuk Try Out ini. Cek lagi nanti ya.')" />
    @endif

    <p class="text-center text-xs text-zinc-500">{{ config('tryout.disclaimer') }}</p>
</div>

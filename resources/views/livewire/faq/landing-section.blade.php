<div>
    @if ($faqs->isNotEmpty())
        <section id="faq" class="relative scroll-mt-24 overflow-hidden bg-warm-sand py-20 sm:py-24 dark:bg-zinc-900/50" data-test="landing-faq-section">
            <div class="pointer-events-none absolute -left-24 top-10 size-72 rounded-full bg-brand-amber/20 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-20 bottom-0 size-72 rounded-full bg-brand-teal/12 blur-3xl" aria-hidden="true"></div>

            <div class="relative mx-auto grid w-full max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:items-start lg:gap-14">
                <div class="lg:sticky lg:top-24">
                    <x-ui.pill variant="brand" icon="chat-bubble-left-right">{{ __('FAQ') }}</x-ui.pill>

                    <h2 class="mt-5 text-3xl leading-[1.08] font-extrabold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
                        {{ __('Pertanyaan yang sering muncul.') }}
                    </h2>

                    <p class="mt-4 max-w-md text-sm leading-relaxed text-zinc-600 sm:text-base dark:text-zinc-300">
                        {{ __('Jawaban singkat untuk hal yang paling sering ditanyakan sebelum mulai persiapan beasiswa.') }}
                    </p>

                    <div class="mt-6">
                        <x-actions.link-button :href="route('faq.index')" iconTrailing="arrow-right">
                            {{ __('Lihat semua FAQ') }}
                        </x-actions.link-button>
                    </div>

                    <p class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Menampilkan :count pertanyaan pilihan dari tim Skular.', ['count' => $faqs->count()]) }}
                    </p>
                </div>

                <div class="flex flex-col gap-3" data-test="landing-faq-list">
                    @foreach ($faqs as $faq)
                        <x-faq.accordion :faq="$faq" level="h3" wire:key="landing-faq-{{ $faq->id }}" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>

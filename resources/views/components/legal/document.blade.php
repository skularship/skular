{{--
    Shared frame of a legal page (<x-legal.document>): title, version and effective
    date from config/legal.php, the "Intinya" summary, a table of contents, then the
    numbered sections passed in the slot (<x-legal.section>).
    Props: document (App\Enums\LegalDocument), summary (list of plain-language points),
    sections (list of {id, title}, in order; ids are stable link anchors).
--}}
@props([
    'document',
    'summary' => [],
    'sections' => [],
])

<article class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 sm:py-14" data-test="legal-document">
    <header>
        <p class="text-sm font-semibold text-brand-teal">{{ __('Informasi legal') }}</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-brand-ink sm:text-4xl">{{ $document->label() }}</h1>
        <p class="mt-3 text-sm text-zinc-600" data-test="legal-version">
            {{ __('Versi :version, berlaku sejak :date', [
                'version' => $document->version(),
                'date' => $document->effectiveAt()->locale('id')->translatedFormat('j F Y'),
            ]) }}
        </p>
    </header>

    @if ($summary !== [])
        <section aria-labelledby="intinya" class="mt-8 rounded-2xl border border-brand-teal/15 bg-white p-5 sm:p-6">
            <h2 id="intinya" class="text-lg font-bold text-brand-ink">{{ __('Intinya') }}</h2>
            <ul class="mt-3 list-disc space-y-2 ps-5 text-sm leading-relaxed text-zinc-700">
                @foreach ($summary as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($sections !== [])
        <nav aria-labelledby="daftar-isi" class="mt-8">
            <h2 id="daftar-isi" class="text-sm font-bold text-brand-ink">{{ __('Daftar isi') }}</h2>
            <ol class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                @foreach ($sections as $index => $section)
                    <li>
                        <a href="#{{ $section['id'] }}" class="text-brand-teal underline-offset-2 hover:underline">
                            {{ $index + 1 }}. {{ $section['title'] }}
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="sk-legal mt-10 space-y-10 text-[0.95rem] leading-relaxed text-zinc-700">
        {{ $slot }}
    </div>

    <footer class="mt-12 border-t border-brand-teal/15 pt-6 text-sm text-zinc-600">
        <p>{{ __('Dokumen lain:') }}</p>
        <x-legal.links class="mt-2" link-class="font-semibold text-brand-teal underline-offset-2 hover:underline" :cookie-settings="false" />
    </footer>
</article>

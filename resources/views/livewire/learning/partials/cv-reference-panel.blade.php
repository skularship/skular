{{-- Praktik step "Data dirimu" panel: the mentee's own CV answers from other modules, shown read only as reference material (Opsi A: show + copy). Nothing here writes to the practice answers, submits anything, or calls the AI; no score or label is shown. Data: cv_sources (Collection<CvSectionData>), open_cv_source (int|null, set only by ModulePlayer::toggleCvSource()). --}}
<x-ui.card padding="none" class="overflow-hidden" data-test="cv-reference-panel" data-tour="cv-reference-panel">
    <section aria-labelledby="cv-reference-heading" class="flex flex-col gap-4 p-5">
        <div class="flex items-start gap-3">
            <x-ui.icon-badge icon="identification" tone="teal" size="sm" />

            <div class="flex flex-col gap-1">
                <h3 id="cv-reference-heading" class="text-base font-bold text-zinc-900">{{ __('Data dirimu') }}</h3>
                <p class="text-sm text-zinc-500">
                    {{ __('Butuh bahan dari pengalamanmu sendiri? Buka jawaban yang sudah kamu isi di modul lain. Tidak ada yang terisi otomatis ke kolom jawabanmu.') }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ($cv_sources as $source)
                @php $is_open = $open_cv_source === $source->practice_form_id; @endphp

                <x-actions.button
                    type="button"
                    size="sm"
                    :variant="$is_open ? 'primary' : 'outline'"
                    :icon="$is_open ? 'chevron-up' : 'chevron-down'"
                    wire:click="toggleCvSource({{ $source->practice_form_id }})"
                    id="cv-source-button-{{ $source->practice_form_id }}"
                    aria-expanded="{{ $is_open ? 'true' : 'false' }}"
                    aria-controls="cv-source-{{ $source->practice_form_id }}"
                    data-test="cv-source-button"
                >
                    {{ $source->buttonText() }}
                </x-actions.button>
            @endforeach
        </div>

        @foreach ($cv_sources as $source)
            @php $is_open = $open_cv_source === $source->practice_form_id; @endphp

            <div
                id="cv-source-{{ $source->practice_form_id }}"
                role="region"
                aria-labelledby="cv-source-button-{{ $source->practice_form_id }}"
                @unless ($is_open) hidden @endunless
                wire:key="cv-source-{{ $source->practice_form_id }}"
            >
                @if ($is_open)
                    <div class="flex flex-col gap-4 rounded-2xl border border-brand-teal/15 bg-brand-teal/5 p-4" data-test="cv-source-card">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-bold text-zinc-900">{{ $source->title }}</p>

                            @if (filled($source->module_title))
                                <x-ui.pill variant="info" icon="book-open">{{ $source->module_title }}</x-ui.pill>
                            @endif
                        </div>

                        @if ($source->filled)
                            <p class="text-xs text-zinc-600">
                                {{ __('Ini jawaban yang sudah kamu isi sebelumnya. Pakai sebagai bahan, lalu tulis jawabanmu sendiri sesuai pertanyaan di bawah.') }}
                            </p>

                            <dl class="flex flex-col gap-3">
                                @foreach ($source->entries as $entry)
                                    <div
                                        class="flex flex-col gap-1.5 rounded-xl border border-zinc-200 bg-white p-3"
                                        x-data="{
                                            copied: false,
                                            failed: false,
                                            done() {
                                                this.copied = true;
                                                this.failed = false;
                                                setTimeout(() => this.copied = false, 2000);
                                            },
                                            fallback(text) {
                                                const area = document.createElement('textarea');
                                                area.value = text;
                                                area.setAttribute('readonly', '');
                                                area.style.position = 'fixed';
                                                area.style.opacity = '0';
                                                document.body.appendChild(area);
                                                area.select();
                                                let ok = false;
                                                try { ok = document.execCommand('copy'); } catch (error) { ok = false; }
                                                area.remove();
                                                ok ? this.done() : (this.failed = true);
                                            },
                                            copy() {
                                                const text = this.$refs.value.innerText;
                                                if (navigator.clipboard && window.isSecureContext) {
                                                    navigator.clipboard.writeText(text).then(() => this.done(), () => this.fallback(text));
                                                } else {
                                                    this.fallback(text);
                                                }
                                            },
                                        }"
                                        data-test="cv-source-entry"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <dt class="text-xs font-semibold text-zinc-500">{{ $entry['label'] }}</dt>

                                            <button
                                                type="button"
                                                @click="copy()"
                                                class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-zinc-200 bg-white px-2 py-1 text-xs font-semibold text-zinc-700 transition hover:border-brand-teal hover:text-brand-teal"
                                                aria-label="{{ __('Salin :label', ['label' => $entry['label']]) }}"
                                                data-test="cv-copy"
                                            >
                                                <x-icons.app-icon name="document-duplicate" class="size-3.5" />
                                                <span x-text="copied ? '{{ __('Tersalin') }}' : '{{ __('Salin') }}'">{{ __('Salin') }}</span>
                                            </button>
                                        </div>

                                        <dd class="text-sm whitespace-pre-line text-zinc-800" x-ref="value">{{ $entry['value'] }}</dd>

                                        <p x-show="failed" x-cloak class="text-xs text-amber-700" role="status">
                                            {{ __('Gagal menyalin otomatis. Blok teksnya lalu salin manual ya.') }}
                                        </p>
                                        <span x-show="copied" x-cloak class="sr-only" role="status">{{ __('Tersalin') }}</span>
                                    </div>
                                @endforeach
                            </dl>

                            @if ($source->module_id !== null)
                                <a
                                    href="{{ route('modules.show', ['module' => $source->module_id, 'section' => 'praktik']) }}"
                                    wire:navigate
                                    class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-brand-teal hover:underline"
                                    data-test="cv-source-edit-link"
                                >
                                    <x-icons.app-icon name="pencil-square" class="size-4" />
                                    {{ __('Perbaiki di modul asal') }}
                                </a>
                            @endif
                        @else
                            <p class="text-sm text-zinc-600" data-test="cv-source-empty">{{ __('Kamu belum mengisi ini.') }}</p>

                            @if ($source->module_id !== null)
                                <a
                                    href="{{ route('modules.show', ['module' => $source->module_id, 'section' => 'praktik']) }}"
                                    wire:navigate
                                    class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-brand-teal hover:underline"
                                    data-test="cv-source-fill-link"
                                >
                                    <x-icons.app-icon name="arrow-right" class="size-4" />
                                    {{ __('Isi dulu di modul :module', ['module' => $source->module_title ?? $source->title]) }}
                                </a>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </section>
</x-ui.card>

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-ui.pill variant="brand" icon="document-arrow-up">{{ __('Pendaftaran beasiswa') }}</x-ui.pill>
            <x-display.heading level="1" size="2xl" class="mt-3">{{ __('Beasiswa Saya') }}</x-display.heading>
            <x-display.text class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ __('Konfirmasi saat kamu sudah mendaftar, unggah bukti screenshot, lalu laporkan hasilnya ketika sudah keluar.') }}
            </x-display.text>
        </div>

        <x-actions.link-button :href="route('profiling.recommendation')" wire:navigate variant="outline" icon="magnifying-glass">
            {{ __('Tambah beasiswa') }}
        </x-actions.link-button>
    </header>

    @if ($applications->isEmpty())
        <x-ui.empty-state
            icon="academic-cap"
            :title="__('Belum ada beasiswa yang kamu pilih')"
            :description="__('Pilih beasiswa dari rekomendasi dulu supaya kamu bisa mencatat pendaftaran dan hasilnya di sini.')"
        >
            <x-actions.link-button :href="route('profiling.recommendation')" wire:navigate iconTrailing="arrow-right">
                {{ __('Lihat rekomendasi') }}
            </x-actions.link-button>
        </x-ui.empty-state>
    @else
        @foreach ($applications as $application)
            @php
                $scholarship = $application->scholarship;
                $is_confirming = $confirming_id === $application->id;
                $is_reporting = $reporting_id === $application->id;
                $result_variant = match ($application->result?->value) {
                    'accepted' => 'success',
                    'rejected' => 'warning',
                    default => 'neutral',
                };
            @endphp

            <x-ui.card class="flex flex-col gap-5" data-test="application-card">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex flex-col gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($application->is_primary)
                                <x-ui.pill variant="brand" icon="star">{{ __('Beasiswa utama') }}</x-ui.pill>
                            @endif

                            <x-ui.pill>{{ $application->status->label() }}</x-ui.pill>

                            @if ($application->result !== null)
                                <x-ui.pill :variant="$result_variant" icon="flag">
                                    {{ $application->result->label() }}
                                </x-ui.pill>
                            @endif
                        </div>

                        <div>
                            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $scholarship?->scholarship_name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $scholarship?->institution }}</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.pill icon="academic-cap">{{ $scholarship?->level->label() }}</x-ui.pill>

                            @if ($scholarship?->closing_date)
                                <x-ui.pill icon="calendar-days">
                                    {{ __('Tutup :date', ['date' => $scholarship->closing_date->translatedFormat('d M Y')]) }}
                                </x-ui.pill>
                            @endif
                        </div>
                    </div>

                    @if ($scholarship?->link)
                        <a
                            href="{{ $scholarship->link }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:border-brand-teal hover:text-brand-teal dark:border-zinc-700 dark:text-zinc-300"
                        >
                            <x-icons.app-icon name="arrow-up-right" class="size-3.5" />
                            {{ __('Situs resmi') }}
                        </a>
                    @endif
                </div>

                <ol class="flex flex-wrap gap-x-6 gap-y-2 border-y border-zinc-100 py-3 text-xs dark:border-zinc-700">
                    @foreach ([
                        ['label' => __('Dipilih'), 'date' => $application->selected_at, 'done' => true],
                        ['label' => __('Pendaftaran dikonfirmasi'), 'date' => $application->submitted_at, 'done' => $application->submitted_at !== null],
                        ['label' => __('Hasil dilaporkan'), 'date' => $application->result_reported_at, 'done' => $application->result_reported_at !== null],
                    ] as $step)
                        <li class="inline-flex items-center gap-1.5 {{ $step['done'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400 dark:text-zinc-500' }}">
                            <x-icons.app-icon :name="$step['done'] ? 'check-circle' : 'minus-circle'" class="size-3.5" />
                            <span class="font-medium">{{ $step['label'] }}</span>
                            @if ($step['date'] !== null)
                                <span class="text-zinc-400">{{ $step['date']->translatedFormat('d M Y') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>

                @if ($application->hasSubmissionProof())
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-900/40">
                        <span class="inline-flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                            <x-icons.app-icon name="photo" class="size-4 text-emerald-500" />
                            {{ __('Bukti screenshot tersimpan') }}
                            @if (filled($application->submission_note))
                                <span class="text-zinc-400">· {{ $application->submission_note }}</span>
                            @endif
                        </span>

                        <a
                            href="{{ route('applications.proof', $application) }}"
                            class="inline-flex items-center gap-1.5 font-medium text-brand-teal transition hover:underline dark:text-brand-teal-light"
                        >
                            <x-icons.app-icon name="document-arrow-up" class="size-3.5" />
                            {{ __('Unduh bukti') }}
                        </a>
                    </div>
                @endif

                @if ($this->needsSubmission($application))
                    @if ($is_confirming)
                        <form wire:submit.prevent="confirmSubmission" class="flex flex-col gap-4 rounded-xl border border-brand-teal/25 bg-brand-teal/5 p-4" data-test="confirm-form">
                            <div>
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Sudah mengirim pendaftaran di situs beasiswa?') }}</p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('Unggah screenshot halaman konfirmasi pendaftaranmu sebagai bukti.') }}
                                </p>
                            </div>

                            <div>
                                <label for="proof-{{ $application->id }}" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Screenshot bukti pendaftaran') }}
                                    <span class="text-xs font-medium text-red-500">{{ __('wajib') }}</span>
                                </label>

                                <input
                                    id="proof-{{ $application->id }}"
                                    type="file"
                                    accept="image/*"
                                    wire:model="proof"
                                    class="block w-full cursor-pointer rounded-lg border border-dashed border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-700 hover:border-brand-teal dark:border-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200"
                                >

                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Format JPG/PNG/WEBP, maksimal 5 MB.') }}</p>

                                @error('proof')
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="note-{{ $application->id }}" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Catatan (opsional)') }}
                                </label>
                                <input
                                    id="note-{{ $application->id }}"
                                    type="text"
                                    wire:model="note"
                                    maxlength="500"
                                    placeholder="{{ __('Contoh: sudah submit lewat portal pukul 14.30.') }}"
                                    class="block h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-base text-zinc-700 shadow-none transition focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-accent focus:ring-offset-accent-foreground sm:h-10 sm:text-sm dark:border-zinc-600 dark:bg-white/10 dark:text-zinc-300"
                                >

                                @error('note')
                                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <x-actions.button type="button" variant="ghost" wire:click="cancelConfirm">{{ __('Batal') }}</x-actions.button>
                                <x-actions.button
                                    type="submit"
                                    icon="check-badge"
                                    loading="scholarship.confirm"
                                    loading-target="confirmSubmission"
                                    data-test="submit-confirmation"
                                >{{ __('Simpan konfirmasi') }}</x-actions.button>
                            </div>

                            <x-loading.inline loading="scholarship.confirm" target="proof,confirmSubmission" />
                        </form>
                    @else
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                {{ __('Belum konfirmasi pendaftaran untuk beasiswa ini.') }}
                            </p>

                            <x-actions.button type="button" icon="document-arrow-up" wire:click="openConfirm({{ $application->id }})" data-test="open-confirm">
                                {{ __('Konfirmasi sudah mendaftar') }}
                            </x-actions.button>
                        </div>
                    @endif
                @endif

                @if ($this->needsResult($application))
                    @if ($is_reporting)
                        <div class="flex flex-col gap-4 rounded-xl border border-brand-teal/25 bg-brand-teal/5 p-4" data-test="report-form">
                            <div>
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Bagaimana hasil pendaftaranmu?') }}</p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('Pilih satu. Keputusan bisa diperbarui kapan saja lewat halaman ini.') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <x-actions.button
                                    type="button"
                                    icon="trophy"
                                    wire:click="reportResult({{ $application->id }}, 'accepted')"
                                    loading="scholarship.result"
                                    data-test="report-accepted"
                                >
                                    {{ __('Saya diterima (Lulus)') }}
                                </x-actions.button>

                                <x-actions.button
                                    type="button"
                                    variant="outline"
                                    icon="flag"
                                    wire:click="reportResult({{ $application->id }}, 'rejected')"
                                    loading="scholarship.result"
                                    data-test="report-rejected"
                                >
                                    {{ __('Belum lulus') }}
                                </x-actions.button>

                                <x-actions.button type="button" variant="ghost" wire:click="cancelReport">{{ __('Batal') }}</x-actions.button>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                {{ __('Pendaftaran sudah dikonfirmasi. Laporkan hasilnya begitu pengumuman keluar.') }}
                            </p>

                            <x-actions.button type="button" icon="flag" wire:click="openReport({{ $application->id }})" data-test="open-report">
                                {{ __('Laporkan hasil') }}
                            </x-actions.button>
                        </div>
                    @endif
                @elseif ($application->result === \App\Enums\ApplicationResult::Accepted)
                    <div class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-950" data-test="accepted-state">
                        <div class="flex items-start gap-3">
                            <x-icons.app-icon name="trophy" class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                            <div>
                                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                                    {{ __('Selamat, kamu diterima! 🎉') }}
                                </p>
                                <p class="mt-1 text-sm text-emerald-700/90 dark:text-emerald-300/90">
                                    {{ __('Bagikan kabar bahagiamu dengan twibbon Skular, lalu tetapkan rencana studimu.') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-actions.link-button :href="route('twibbon.show', $application)" wire:navigate icon="sparkles" data-test="twibbon-link">
                                {{ __('Buat twibbon') }}
                            </x-actions.link-button>
                        </div>
                    </div>
                @elseif ($application->result === \App\Enums\ApplicationResult::Rejected)
                    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-950" data-test="rejected-state">
                        <x-icons.app-icon name="fire" class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                        <div>
                            <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                {{ __('Terima kasih sudah melapor. Belum berhasil kali ini.') }}
                            </p>
                            <p class="mt-1 text-sm text-amber-800/90 dark:text-amber-200/90">
                                {{ __('Evaluasi feedback AI dan perbaiki bagian yang kurang, lalu coba beasiswa berikutnya. Banyak mentee berhasil di percobaan kedua.') }}
                            </p>
                        </div>
                    </div>
                @endif
            </x-ui.card>
        @endforeach
    @endif
</div>

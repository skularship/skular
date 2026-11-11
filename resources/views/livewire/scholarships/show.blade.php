@php
    $days = $scholarship->daysRemaining();
    $urgent = $scholarship->isOpen() && $days !== null && $days <= 14;
@endphp

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <x-actions.link-button :href="route('scholarships.index')" wire:navigate variant="ghost" icon="arrow-left">
            {{ __('Semua Beasiswa') }}
        </x-actions.link-button>

        <x-actions.link-button :href="route('dashboard')" wire:navigate variant="ghost" iconTrailing="arrow-right">
            {{ __('Dashboard') }}
        </x-actions.link-button>
    </div>

    <section class="sk-rise relative overflow-hidden rounded-4xl bg-linear-to-br from-brand-teal to-brand-teal-deep p-6 text-white shadow-soft sm:p-8" data-test="scholarship-detail">
        <div class="pointer-events-none absolute -top-20 -right-14 size-56 rounded-full bg-brand-amber/25 blur-3xl" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-col gap-5">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur-sm">
                    <x-icons.app-icon name="academic-cap" class="size-3.5" />
                    {{ $scholarship->level->label() }}
                </span>

                <span @class([
                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold',
                    'bg-brand-amber text-brand-ink' => $scholarship->isOpen(),
                    'bg-white/20 text-white' => ! $scholarship->isOpen(),
                ])>
                    {{ $scholarship->status->label() }}
                </span>

                @if ($scholarship->scope)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur-sm">
                        <x-icons.app-icon :name="$scholarship->scope->isStudyDestination() ? 'globe-alt' : 'banknotes'" class="size-3.5" />
                        {{ $scholarship->scope->label() }}
                    </span>
                @endif

                @if ($urgent)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-400/90 px-3 py-1 text-xs font-bold text-red-950">
                        <x-icons.app-icon name="fire" class="size-3.5" />
                        {{ trans_choice(':count hari lagi|:count hari lagi', $days, ['count' => $days]) }}
                    </span>
                @endif
            </div>

            <div>
                <h1 class="text-3xl font-extrabold tracking-tight break-words sm:text-4xl">{{ $scholarship->scholarship_name }}</h1>
                @if (filled($scholarship->institution))
                    <p class="mt-1.5 text-sm text-white/80 sm:text-base">{{ $scholarship->institution }}</p>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($scholarship->location)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/12 px-3 py-1 text-xs font-medium text-white">
                        <x-icons.app-icon name="map-pin" class="size-3.5" />
                        {{ $scholarship->location }}
                    </span>
                @endif
                @if ($scholarship->estimated_quota)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/12 px-3 py-1 text-xs font-medium text-white">
                        <x-icons.app-icon name="users" class="size-3.5" />
                        {{ __('Kuota :quota', ['quota' => $scholarship->estimated_quota]) }}
                    </span>
                @endif
                @if ($scholarship->cluster)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/12 px-3 py-1 text-xs font-medium text-white">
                        <x-icons.app-icon name="sparkles" class="size-3.5" />
                        {{ $scholarship->cluster->label }}
                    </span>
                @endif
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <p class="text-[0.6875rem] font-semibold tracking-wide text-white/70 uppercase">{{ __('Dibuka') }}</p>
                    <p class="mt-0.5 text-sm font-bold text-white">{{ $scholarship->opening_date?->translatedFormat('d M Y') ?? __('Belum ditentukan') }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <p class="text-[0.6875rem] font-semibold tracking-wide text-white/70 uppercase">{{ __('Ditutup') }}</p>
                    <p class="mt-0.5 text-sm font-bold text-white">{{ $scholarship->closing_date?->translatedFormat('d M Y') ?? __('Belum ditentukan') }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <p class="text-[0.6875rem] font-semibold tracking-wide text-white/70 uppercase">{{ __('Sisa hari') }}</p>
                    <p class="mt-0.5 text-sm font-bold text-white">{{ $days === null ? '—' : __(':days hari', ['days' => $days]) }}</p>
                </div>
            </div>

            @if ($application)
                <x-feedback.callout variant="success" :heading="__('Beasiswa ini sudah ada di daftarmu')" data-test="already-selected">
                    {{ __('Status: :status. Lanjutkan persiapan dan kerjakan modulnya.', ['status' => $application->status->label()]) }}
                </x-feedback.callout>
            @endif

            @error('selection')
                <x-feedback.callout variant="warning" :heading="__('Tidak bisa ditambahkan')" data-test="selection-error">
                    {{ $message }}
                </x-feedback.callout>
            @enderror

            <div class="flex flex-wrap items-center gap-3">
                @if ($application)
                    <x-actions.link-button :href="route('dashboard')" wire:navigate iconTrailing="arrow-right" data-test="continue-preparation">
                        {{ __('Lanjutkan Persiapan') }}
                    </x-actions.link-button>
                @elseif (! $scholarship->isOpen())
                    <x-actions.button type="button" disabled icon="lock-closed">
                        {{ __('Pendaftaran ditutup') }}
                    </x-actions.button>
                @elseif ($at_limit)
                    <x-actions.button type="button" disabled icon="lock-closed" data-test="limit-reached">
                        {{ __('Kuota :count beasiswa penuh', ['count' => $max_selections]) }}
                    </x-actions.button>
                @else
                    <x-actions.button
                        type="button"
                        icon="plus"
                        wire:click="addToSelection"
                        loading="scholarship.save"
                        data-test="add-selection"
                    >
                        {{ __('Tambah ke pilihan beasiswa') }}
                    </x-actions.button>
                    <span class="text-xs text-white/80">
                        {{ __('Sisa :count slot pilihan.', ['count' => $remaining_slots]) }}
                    </span>
                @endif

                @if (filled($scholarship->link))
                    <a
                        href="{{ $scholarship->link }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                    >
                        <x-icons.app-icon name="arrow-up-right" class="size-4" />
                        {{ __('Situs resmi') }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Match label and reasons for the signed-in mentee (moved off the recommendation card). Label only, never the score. --}}
    @if ($match)
        @php
            $tier_variant = match ($match->tier->getColor()) {
                'success' => 'success',
                'warning' => 'warning',
                default => 'info',
            };
        @endphp

        <x-ui.card class="flex flex-col gap-4" data-test="match-reasons">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <x-ui.icon-badge icon="sparkles" tone="teal" />
                    <h2 class="text-lg font-bold text-zinc-900">{{ __('Kecocokan dengan profilmu') }}</h2>
                </div>

                <x-ui.pill :variant="$tier_variant" icon="sparkles">{{ $match->tier->label() }}</x-ui.pill>
            </div>

            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($match->reasons as $reason)
                    <li class="flex items-start gap-2 rounded-2xl bg-zinc-50 px-4 py-3 text-sm">
                        <x-icons.app-icon
                            :name="$reason->satisfied ? 'check-circle' : 'exclamation-triangle'"
                            class="mt-0.5 size-4 shrink-0 {{ $reason->satisfied ? 'text-emerald-500' : 'text-amber-500' }}"
                        />

                        <div>
                            <span class="text-zinc-500">{{ $reason->label }}:</span>
                            <span class="text-zinc-700">{{ $reason->detail }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    @if ($scholarship->hasKeyBenefits())
        <x-ui.card variant="amber" class="flex flex-col gap-4" data-test="key-benefits">
            <div class="flex items-center gap-3">
                <x-ui.icon-badge icon="gift" tone="amber" />
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Benefit Utama') }}</h2>
            </div>

            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($scholarship->keyBenefits() as $benefit)
                    <li class="flex items-start gap-2.5 rounded-2xl bg-white/70 px-3.5 py-3 text-sm text-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-200">
                        <x-icons.app-icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-brand-orange" />
                        <span>{{ $benefit }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    @if ($scholarship->hasValuesSought())
        <x-ui.card class="flex flex-col gap-3" data-test="values-sought">
            <div class="flex items-center gap-3">
                <x-ui.icon-badge icon="sparkles" tone="teal" />
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Nilai yang Dicari') }}</h2>
            </div>

            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Karakteristik yang dihargai penyelenggara. Ini bukan syarat mutlak yang menggugurkan.') }}
            </p>

            <div class="flex flex-wrap gap-2">
                @foreach ($scholarship->valuesSought() as $value)
                    <x-ui.pill variant="info" icon="sparkles">{{ $value }}</x-ui.pill>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <x-ui.card class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
            <x-ui.icon-badge icon="clipboard-document-check" tone="peach" />
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Persyaratan') }}</h2>
        </div>

        <dl class="grid gap-3 sm:grid-cols-2">
            <div class="flex items-center justify-between rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-900/40">
                <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('IPK minimum') }}</dt>
                <dd class="text-sm font-bold text-zinc-800 dark:text-zinc-100">
                    {{ $scholarship->minimumGpa() === null ? __('Tidak ada') : number_format($scholarship->minimumGpa(), 2) }}
                </dd>
            </div>
            <div class="flex items-center justify-between rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-900/40">
                <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Usia maksimal') }}</dt>
                <dd class="text-sm font-bold text-zinc-800 dark:text-zinc-100">
                    {{ $scholarship->maximum_age === null ? __('Tidak ada') : __(':age tahun', ['age' => $scholarship->maximum_age]) }}
                </dd>
            </div>
            <div class="flex items-center justify-between rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-900/40">
                <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Wajib SKTM/KHI') }}</dt>
                <dd class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ $scholarship->sktm_require ? __('Ya') : __('Tidak') }}</dd>
            </div>
            <div class="flex items-center justify-between rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-900/40">
                <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tanggal verifikasi') }}</dt>
                <dd class="text-sm font-bold text-zinc-800 dark:text-zinc-100">
                    {{ $scholarship->verification_date?->translatedFormat('d M Y') ?? __('Belum ditentukan') }}
                </dd>
            </div>
        </dl>
    </x-ui.card>

    @if (filled($scholarship->note))
        <x-ui.card variant="teal" class="flex flex-col gap-2">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Catatan') }}</h2>
            <p class="text-sm leading-relaxed whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $scholarship->note }}</p>
        </x-ui.card>
    @endif

    {{-- "Informasi Tidak Akurat?": mentees flag wrong data; admins review it in Filament (ScholarshipInfoReports). --}}
    <div class="flex flex-col gap-3 rounded-3xl border border-dashed border-brand-teal/25 bg-white/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between" data-test="report-info">
        <div class="flex items-start gap-3">
            <x-icons.app-icon name="flag" class="mt-0.5 size-5 text-brand-orange" />
            <div>
                <p class="text-sm font-semibold text-zinc-900">{{ __('Ada info yang tidak sesuai?') }}</p>
                <p class="text-xs text-zinc-500">{{ __('Bantu kami menjaga data beasiswa tetap akurat. Laporanmu akan dicek tim Skular.') }}</p>
            </div>
        </div>

        <x-actions.button type="button" variant="outline" icon="flag" wire:click="$set('show_report_modal', true)" class="shrink-0" data-test="open-report">
            {{ __('Informasi Tidak Akurat?') }}
        </x-actions.button>
    </div>

    <x-overlay.modal name="report-info" model="show_report_modal" class="max-w-lg" focusable>
        <form wire:submit="submitReport" class="flex flex-col gap-4" data-test="report-form">
            <div class="pe-8">
                <h2 class="text-lg font-bold text-zinc-900">{{ __('Laporkan informasi tidak akurat') }}</h2>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ __('Ceritakan bagian mana yang menurutmu salah di halaman :name. Tim Skular akan mengeceknya sebelum mengubah data.', ['name' => $scholarship->scholarship_name]) }}
                </p>
            </div>

            @error('report')
                <x-feedback.callout variant="warning" :heading="__('Laporan belum terkirim')" data-test="report-error">
                    {{ $message }}
                </x-feedback.callout>
            @enderror

            <x-forms.select wire:model="report_field" :label="__('Bagian yang tidak akurat')" required>
                <option value="">{{ __('Pilih bagian') }}</option>
                @foreach ($report_fields as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-forms.select>

            <div class="w-full">
                <label for="report-suggested-value" class="mb-1.5 block text-sm font-medium text-zinc-700">{{ __('Informasi yang menurutmu benar') }}</label>
                <textarea
                    id="report-suggested-value"
                    wire:model="report_suggested_value"
                    rows="3"
                    maxlength="1000"
                    required
                    placeholder="{{ __('Contoh: Deadline diperpanjang sampai 30 November 2026.') }}"
                    @class([
                        'block w-full rounded-lg border bg-white px-3 py-2 text-base text-zinc-700 placeholder:text-zinc-400 focus:outline-hidden focus:ring-2 focus:ring-accent sm:text-sm',
                        'border-red-500' => $errors->has('report_suggested_value'),
                        'border-zinc-200' => ! $errors->has('report_suggested_value'),
                    ])
                ></textarea>
                @error('report_suggested_value')
                    <p class="mt-1.5 text-sm text-red-600" data-test="error-report_suggested_value">{{ $message }}</p>
                @enderror
            </div>

            <x-forms.input
                type="url"
                wire:model="report_source_url"
                :label="__('Tautan bukti atau sumber (opsional)')"
                placeholder="https://"
                maxlength="2048"
            />

            <div class="w-full">
                <label for="report-note" class="mb-1.5 block text-sm font-medium text-zinc-700">{{ __('Catatan tambahan (opsional)') }}</label>
                <textarea
                    id="report-note"
                    wire:model="report_note"
                    rows="2"
                    maxlength="1000"
                    class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-base text-zinc-700 placeholder:text-zinc-400 focus:outline-hidden focus:ring-2 focus:ring-accent sm:text-sm"
                ></textarea>
                @error('report_note')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <p class="text-xs text-zinc-500">{{ __('Tautan bukti hanya dilihat tim Skular dan tidak ditampilkan ke mentee lain.') }}</p>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-actions.button type="button" variant="outline" wire:click="$set('show_report_modal', false)">
                    {{ __('Batal') }}
                </x-actions.button>
                <x-actions.button type="submit" icon="paper-airplane" data-test="submit-report">
                    {{ __('Kirim laporan') }}
                </x-actions.button>
            </div>
        </form>
    </x-overlay.modal>
</div>

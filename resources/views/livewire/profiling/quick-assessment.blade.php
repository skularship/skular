@php
    use App\Enums\QuickProfilingAsset;
    use App\Enums\QuickProfilingGoal;
    use App\Enums\QuickProfilingStatus;
    use App\Enums\QuickProfilingTimeline;
    use App\Enums\QuickProfilingWeeklyTime;
    use App\Enums\ReadinessCategory;
@endphp

<section
    id="cek-kesiapan"
    class="relative scroll-mt-24 overflow-hidden bg-linear-160 from-brand-teal via-brand-teal/90 to-brand-ink py-20 text-white sm:py-28"
    aria-labelledby="quick-assessment-heading"
>
    <div class="brand-grid pointer-events-none absolute inset-0 opacity-15" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -top-24 -right-16 size-80 rounded-full bg-brand-amber/20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-28 -left-16 size-80 rounded-full bg-brand-orange/20 blur-3xl" aria-hidden="true"></div>

    <div class="relative mx-auto grid w-full max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:items-start lg:gap-14">
        <div class="lg:sticky lg:top-24">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-amber px-3 py-1 text-xs font-bold tracking-wide text-brand-ink uppercase">
                <x-icons.app-icon name="bolt" class="size-3.5" />
                {{ __('Profiling Kilat') }}
            </span>

            <h2 id="quick-assessment-heading" class="mt-5 text-3xl leading-[1.08] font-extrabold tracking-tight text-white sm:text-5xl">
                {{ __('Seberapa Siap Kamu Mengejar Beasiswa?') }}
            </h2>

            <p class="mt-5 max-w-lg text-sm leading-relaxed text-brand-cream/80 sm:text-base">
                {{ __('Jawab beberapa pertanyaan singkat dan temukan aspek persiapan yang bisa kamu tingkatkan. Gratis, tanpa perlu mendaftar.') }}
            </p>

            <ul class="mt-6 flex flex-col gap-3 text-sm text-brand-cream/85">
                @foreach ([
                    '5 pertanyaan, selesai kurang dari satu menit',
                    'Tanpa login, tanpa email, tanpa nomor telepon',
                    'Skor, hambatan, dan rekomendasi muncul langsung',
                ] as $point)
                    <li class="flex items-start gap-2">
                        <x-icons.app-icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-brand-amber" />
                        <span>{{ $point }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="rounded-3xl border border-white/15 bg-warm-cream p-5 shadow-2xl sm:p-8 dark:border-white/10 dark:bg-zinc-900">
            @if ($this->result)
                @php
                    $result = $this->result;
                    $radius = 52;
                    $circumference = 2 * M_PI * $radius;
                    $offset = $circumference * (1 - $result->score / 100);

                    $categoryVariant = match ($result->category) {
                        ReadinessCategory::StrongFoundation => 'success',
                        ReadinessCategory::BuildingReadiness => 'warning',
                        default => 'brand',
                    };
                @endphp

                <div class="flex flex-col gap-6" data-test="quick-result">
                    <div class="flex flex-col items-center gap-6 text-center sm:flex-row sm:items-center sm:text-left">
                        <div class="relative shrink-0" role="img" aria-label="{{ __('Skor kesiapan: :score dari 100', ['score' => $result->score]) }}">
                            <svg viewBox="0 0 128 128" class="size-32 -rotate-90" aria-hidden="true">
                                <circle cx="64" cy="64" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="12" class="text-zinc-200 dark:text-white/10" />
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="{{ $radius }}"
                                    fill="none"
                                    stroke-width="12"
                                    stroke-linecap="round"
                                    stroke="url(#readinessGradient)"
                                    stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $offset }}"
                                    class="transition-[stroke-dashoffset] duration-700 ease-out motion-reduce:transition-none"
                                />
                                <defs>
                                    <linearGradient id="readinessGradient" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#F2C94C" />
                                        <stop offset="100%" stop-color="#F2994A" />
                                    </linearGradient>
                                </defs>
                            </svg>

                            <div class="absolute inset-0 flex flex-col items-center justify-center" aria-hidden="true">
                                <span class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $result->score }}</span>
                                <span class="text-[0.625rem] font-semibold tracking-wide text-zinc-400 uppercase">/ 100</span>
                            </div>
                        </div>

                        <div>
                            <x-ui.pill :variant="$categoryVariant" :icon="$result->category->icon()">
                                {{ $result->categoryLabel() }}
                            </x-ui.pill>

                            <p class="mt-3 text-lg font-bold text-zinc-900 dark:text-white">{{ $result->headline }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $result->subheadline }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-brand-amber/40 bg-brand-amber/10 p-4">
                        <p class="flex items-center gap-2 text-xs font-semibold tracking-wide text-brand-teal uppercase dark:text-brand-amber">
                            <x-icons.app-icon name="exclamation-triangle" class="size-4" />
                            {{ __('Hambatan utama') }}
                        </p>
                        <p class="mt-1.5 text-sm text-zinc-700 dark:text-zinc-200">{{ $result->blocker }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold tracking-wide text-brand-teal uppercase dark:text-brand-teal-light">
                            {{ __('Tiga langkah berikutnya') }}
                        </p>

                        <ol class="mt-3 flex flex-col gap-2.5">
                            @foreach ($result->recommendations as $index => $recommendation)
                                <li class="flex items-start gap-3 rounded-xl border border-zinc-200/80 bg-white/70 p-3 dark:border-white/10 dark:bg-white/5">
                                    <span class="brand-gradient mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold text-brand-ink" aria-hidden="true">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $recommendation }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    <div class="rounded-2xl border border-brand-teal/20 bg-brand-teal/5 p-4 dark:border-brand-teal/30 dark:bg-brand-teal/10">
                        <p class="flex items-center gap-2 text-xs font-semibold tracking-wide text-brand-teal uppercase dark:text-brand-teal-light">
                            <x-icons.app-icon name="calendar-days" class="size-4" />
                            {{ __('Ringkasan waktu') }}
                        </p>
                        <p class="mt-1.5 text-sm text-zinc-700 dark:text-zinc-200">{{ $result->timeline_summary }}</p>
                    </div>

                    <div class="rounded-2xl border border-brand-teal/15 bg-linear-135 from-brand-amber/20 via-white/40 to-brand-teal/10 p-5 dark:border-white/10 dark:from-brand-amber/10 dark:via-zinc-900 dark:to-brand-teal/15">
                        <p class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Bangun Kesiapanmu Bersama Skular') }}</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ __('Daftar untuk mulai menyusun langkah persiapan beasiswamu secara lebih terarah.') }}
                        </p>

                        <div class="mt-4 flex flex-col-reverse gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                            <x-actions.link-button :href="route('register')" iconTrailing="arrow-right" data-test="quick-cta-register">
                                {{ __('Daftar Sekarang') }}
                            </x-actions.link-button>

                            <x-actions.button type="button" variant="outline" icon="arrow-path" wire:click="restart" data-test="quick-restart">
                                {{ __('Hitung Ulang Jawaban') }}
                            </x-actions.button>
                        </div>
                    </div>

                    <p class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                        {{ __('Skor ini adalah indikator kesiapan persiapan, bukan peluang lolos beasiswa. Hasil dan jawabanmu tidak disimpan di akun mana pun.') }}
                    </p>
                </div>
            @else
                <form wire:submit="advance" class="flex flex-col gap-5" data-test="quick-form">
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold tracking-wide text-brand-teal uppercase dark:text-brand-teal-light">
                            <span>{{ __('Pertanyaan :current dari :total', ['current' => $step, 'total' => count($this->steps)]) }}</span>
                            <span>{{ $this->progressPercentage }}%</span>
                        </div>

                        <div
                            class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-200/80 dark:bg-white/10"
                            role="progressbar"
                            aria-valuenow="{{ $this->progressPercentage }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="{{ __('Progres cek kesiapan') }}"
                        >
                            <div class="h-2 brand-gradient rounded-full transition-all duration-500 motion-reduce:transition-none" style="width: {{ $this->progressPercentage }}%"></div>
                        </div>
                    </div>

                    <fieldset>
                        <legend class="block">
                            <span class="block text-lg font-bold text-zinc-900 dark:text-white">{{ $this->currentTitle }}</span>
                            <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">{{ $this->currentDescription }}</span>
                        </legend>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @if ($this->activeField === 'currentStatus')
                                @foreach (QuickProfilingStatus::cases() as $option)
                                    <x-forms.selection-card
                                        wire:click="selectChoice('currentStatus', '{{ $option->value }}')"
                                        :value="$option->value"
                                        :selected="$currentStatus === $option->value"
                                        :title="$option->getLabel()"
                                        :description="$option->description()"
                                        :icon="$option->icon()"
                                        data-test="option-status-{{ $option->value }}"
                                    />
                                @endforeach
                            @elseif ($this->activeField === 'target')
                                @foreach (QuickProfilingGoal::cases() as $option)
                                    <x-forms.selection-card
                                        wire:click="selectChoice('target', '{{ $option->value }}')"
                                        :value="$option->value"
                                        :selected="$target === $option->value"
                                        :title="$option->getLabel()"
                                        :description="$option->description()"
                                        :icon="$option->icon()"
                                        data-test="option-target-{{ $option->value }}"
                                    />
                                @endforeach
                            @elseif ($this->activeField === 'registerTimeline')
                                @foreach (QuickProfilingTimeline::cases() as $option)
                                    <x-forms.selection-card
                                        wire:click="selectChoice('registerTimeline', '{{ $option->value }}')"
                                        :value="$option->value"
                                        :selected="$registerTimeline === $option->value"
                                        :title="$option->getLabel()"
                                        :icon="$option->icon()"
                                        data-test="option-timeline-{{ $option->value }}"
                                    />
                                @endforeach
                            @elseif ($this->activeField === 'assets')
                                <div class="sm:col-span-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach (QuickProfilingAsset::cases() as $option)
                                        <x-forms.selection-card
                                            wire:click="toggleAsset('{{ $option->value }}')"
                                            :value="$option->value"
                                            :selected="in_array($option->value, $assets, true)"
                                            :title="$option->getLabel()"
                                            :icon="$option->icon()"
                                            data-test="option-asset-{{ $option->value }}"
                                        />
                                    @endforeach
                                </div>
                            @elseif ($this->activeField === 'weeklyPrepTime')
                                @foreach (QuickProfilingWeeklyTime::cases() as $option)
                                    <x-forms.selection-card
                                        wire:click="selectChoice('weeklyPrepTime', '{{ $option->value }}')"
                                        :value="$option->value"
                                        :selected="$weeklyPrepTime === $option->value"
                                        :title="$option->getLabel()"
                                        :icon="$option->icon()"
                                        data-test="option-weekly-{{ $option->value }}"
                                    />
                                @endforeach
                            @endif
                        </div>
                    </fieldset>

                    @error($this->activeField)
                        <x-feedback.callout variant="warning" icon="exclamation-triangle" :heading="__('Periksa lagi ya')">
                            {{ $message }}
                        </x-feedback.callout>
                    @enderror

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200/80 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                        <div>
                            @if ($step > 1)
                                <x-actions.button type="button" variant="ghost" icon="arrow-left" wire:click="goBack" data-test="quick-back">
                                    {{ __('Kembali') }}
                                </x-actions.button>
                            @endif
                        </div>

                        @if ($step < count($this->steps))
                            <x-actions.button
                                type="submit"
                                iconTrailing="arrow-right"
                                loading="profiling.save"
                                loading-target="advance"
                                data-test="quick-next"
                            >
                                {{ __('Lanjut') }}
                            </x-actions.button>
                        @else
                            <x-actions.button
                                type="submit"
                                iconTrailing="sparkles"
                                loading="profiling.readiness"
                                loading-target="advance"
                                data-test="quick-submit"
                            >
                                {{ __('Lihat Hasil Kesiapan') }}
                            </x-actions.button>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>

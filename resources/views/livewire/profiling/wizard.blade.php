<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <header class="flex flex-col gap-5 rounded-3xl border border-brand-amber/30 bg-linear-135 from-brand-amber/15 via-white/40 to-brand-teal/5 p-6 sm:p-8 dark:border-brand-amber/20 dark:from-brand-amber/10 dark:via-zinc-900 dark:to-brand-teal/10">
        <x-ui.pill variant="brand" icon="identification">Profiling</x-ui.pill>

        <div>
            <x-display.heading level="1" size="2xl">{{ __('Kenalan dulu, yuk') }}</x-display.heading>
            <x-display.text class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ __('Jawab 8 pertanyaan singkat supaya Skular bisa mencocokkanmu dengan beasiswa yang paling relevan.') }}
            </x-display.text>
        </div>

        <div class="flex items-center gap-4">
            <span class="brand-gradient flex size-12 shrink-0 items-center justify-center rounded-2xl text-base font-bold text-brand-ink shadow-sm" aria-hidden="true">
                {{ str_pad((string) $step, 2, '0', STR_PAD_LEFT) }}
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold tracking-wide text-brand-teal uppercase dark:text-brand-teal-light">
                    {{ __('Langkah :current dari :total', ['current' => $step, 'total' => count($this->steps)]) }}
                </p>
                <p class="text-base leading-snug font-bold break-words text-zinc-900 sm:text-lg dark:text-white">{{ $this->currentLabel }}</p>
            </div>

            <span class="shrink-0 text-xs font-medium text-zinc-400">
                {{ $this->progress['percentage'] }}%
            </span>
        </div>

        <div
            class="h-2 w-full overflow-hidden rounded-full bg-white/60 dark:bg-white/10"
            role="progressbar"
            aria-valuenow="{{ $this->progress['percentage'] }}"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="{{ __('Progres profiling') }}"
        >
            <div class="h-2 brand-gradient rounded-full transition-all duration-500" style="width: {{ $this->progress['percentage'] }}%"></div>
        </div>

        <ol class="flex flex-wrap gap-1 sm:gap-1.5" aria-label="{{ __('Progres langkah') }}">
            @foreach ($this->steps as $index => $key)
                @php
                    $number = $index + 1;
                @endphp
                <li>
                    <button
                        type="button"
                        wire:click="goToStep({{ $number }})"
                        data-test="stepper-dot-{{ $key }}"
                        title="{{ \App\Livewire\Profiling\Wizard::STEP_LABELS[$key] ?? '' }}"
                        @class([
                            'flex size-7 items-center justify-center rounded-full border text-[0.625rem] font-semibold transition sm:size-6',
                            'border-transparent brand-gradient text-brand-ink' => $number < $step,
                            'border-brand-orange text-brand-orange ring-2 ring-brand-orange/25' => $number === $step,
                            'border-zinc-200 text-zinc-400 hover:border-brand-orange/50 hover:text-brand-orange dark:border-zinc-600' => $number > $step,
                        ])
                        aria-current="{{ $number === $step ? 'step' : 'false' }}"
                        aria-label="{{ __('Langkah :number: :label', ['number' => $number, 'label' => \App\Livewire\Profiling\Wizard::STEP_LABELS[$key] ?? '']) }}"
                    >
                        @if ($number < $step)
                            <x-icons.app-icon name="check" class="size-3" />
                        @else
                            {{ $number }}
                        @endif
                    </button>
                </li>
            @endforeach
        </ol>
    </header>

    <x-ui.card class="border-brand-teal/15">
        <form wire:submit="submitStep" class="flex flex-col gap-6">
            @if ($this->activeStep === 'level')
                <fieldset class="flex flex-col gap-4">
                    <legend class="flex flex-col gap-1 mb-3">
                        <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Pilih jenjang pendidikanmu saat ini') }}</span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Jenjang ini menentukan jenis beasiswa yang bisa kamu lamar.') }}</span>
                    </legend>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->levelOptions as $value => $label)
                            <x-forms.selection-card
                                wire:click="selectChoice('level', '{{ $value }}')"
                                :value="$value"
                                :selected="$level === $value"
                                :title="$label"
                                icon="academic-cap"
                                data-test="option-level-{{ $value }}"
                            />
                        @endforeach
                    </div>
                </fieldset>

                @error('level')
                    <x-feedback.callout variant="warning" :heading="__('Pilih dulu ya')">{{ $message }}</x-feedback.callout>
                @enderror
            @elseif ($this->activeStep === 'age')
                <x-forms.input
                    wire:model="age"
                    type="number"
                    min="15"
                    max="60"
                    :label="__('Berapa usiamu sekarang?')"
                    :placeholder="__('Contoh: 21')"
                />
            @elseif ($this->activeStep === 'gpa')
                <x-forms.input
                    wire:model="gpa"
                    type="number"
                    step="0.01"
                    min="0"
                    max="4"
                    :label="__('Berapa IPK terakhirmu?')"
                    :placeholder="__('Contoh: 3.75')"
                />
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Gunakan skala 0-4. Kalau belum punya IPK, isi perkiraan terbaikmu.') }}
                </p>
            @elseif ($this->activeStep === 'has_sktm')
                <fieldset class="flex flex-col gap-4">
                    <legend class="flex flex-col gap-1">
                        <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Apakah kamu memiliki SKTM/KHI?') }}</span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sebagian beasiswa mensyaratkan SKTM/KHI. Jawabanmu tidak mempengaruhi statusmu.') }}</span>
                    </legend>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-forms.selection-card
                            wire:click="selectChoice('has_sktm', '1')"
                            :value="'1'"
                            :selected="$has_sktm === true"
                            :title="__('Ya, saya punya')"
                            :description="__('Punya Surat Keterangan Tidak Mampu atau Kartu Indonesia Sehat.')"
                            icon="document-text"
                        />

                        <x-forms.selection-card
                            wire:click="selectChoice('has_sktm', '0')"
                            :value="'0'"
                            :selected="$has_sktm === false"
                            :title="__('Belum / tidak ada')"
                            :description="__('Kamu tetap bisa mengajukan banyak beasiswa tanpa dokumen ini.')"
                            icon="x-circle"
                        />
                    </div>
                </fieldset>

                @error('has_sktm')
                    <x-feedback.callout variant="warning" :heading="__('Pilih dulu ya')">{{ $message }}</x-feedback.callout>
                @enderror
            @elseif ($this->activeStep === 'desired_level')
                <fieldset class="flex flex-col gap-4">
                    <legend class="flex flex-col gap-1">
                        <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Jenjang apa yang ingin kamu kejar?') }}</span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pilih satu target jenjang pendidikan.') }}</span>
                    </legend>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->levelOptions as $value => $label)
                            <x-forms.selection-card
                                wire:click="selectChoice('desired_level', '{{ $value }}')"
                                :value="$value"
                                :selected="$desired_level === $value"
                                :title="$label"
                                icon="arrow-up-circle"
                                data-test="option-desired-level-{{ $value }}"
                            />
                        @endforeach
                    </div>
                </fieldset>

                @error('desired_level')
                    <x-feedback.callout variant="warning" :heading="__('Pilih dulu ya')">{{ $message }}</x-feedback.callout>
                @enderror
            @elseif ($this->activeStep === 'desired_location')
                <fieldset class="flex flex-col gap-4">
                    <legend class="flex flex-col gap-1">
                        <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Kamu ingin kuliah di mana?') }}</span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pilih satu lokasi tujuan kuliah.') }}</span>
                    </legend>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($this->locationOptions as $value => $label)
                            <x-forms.selection-card
                                wire:click="selectChoice('desired_location', '{{ $value }}')"
                                :value="$value"
                                :selected="$desired_location === $value"
                                :title="$label"
                                :description="$value === 'dalam_negeri' ? __('Universitas di Indonesia') : __('Universitas di luar negeri')"
                                :icon="$value === 'dalam_negeri' ? 'flag' : 'globe-alt'"
                                data-test="option-desired-location-{{ $value }}"
                            />
                        @endforeach
                    </div>
                </fieldset>

                @error('desired_location')
                    <x-feedback.callout variant="warning" :heading="__('Pilih dulu ya')">{{ $message }}</x-feedback.callout>
                @enderror
            @elseif ($this->activeStep === 'cluster')
                <fieldset class="flex flex-col gap-4">
                    <legend class="flex flex-col gap-1">
                        <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Bidang studi yang paling kamu minati') }}</span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pilih satu bidang. Ini menentukan modul pembelajaranmu.') }}</span>
                    </legend>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($this->clusters as $clusterOption)
                            <x-forms.selection-card
                                wire:click="selectCluster('{{ $clusterOption->id }}')"
                                :value="(string) $clusterOption->id"
                                :selected="(string) $cluster === (string) $clusterOption->id"
                                :title="$clusterOption->label"
                                :description="$clusterOption->description"
                                icon="academic-cap"
                                data-test="option-cluster-{{ $clusterOption->id }}"
                            />
                        @endforeach
                    </div>
                </fieldset>

                @error('cluster')
                    <x-feedback.callout variant="warning" :heading="__('Pilih dulu ya')">{{ $message }}</x-feedback.callout>
                @enderror
            @elseif ($this->activeStep === 'phone')
                <x-forms.input
                    wire:model="phone"
                    type="tel"
                    :label="__('Nomor telepon aktif')"
                    :placeholder="__('Contoh: 081234567890')"
                    autocomplete="tel"
                />
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Kami hanya memakainya untuk mengirim pengingat deadline beasiswamu.') }}
                </p>
            @endif

            <div class="flex flex-col-reverse gap-3 border-t border-brand-amber/20 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                <div>
                    @if ($step > 1)
                        <x-actions.button type="button" variant="ghost" icon="arrow-left" wire:click="goBack" data-test="profiling-back">
                            {{ __('Kembali') }}
                        </x-actions.button>
                    @endif
                </div>

                @if ($step < count($this->steps))
                    <x-actions.button
                        type="submit"
                        iconTrailing="arrow-right"
                        loading="profiling.save"
                        loading-target="submitStep"
                        data-test="profiling-next"
                    >
                        {{ __('Lanjut') }}
                    </x-actions.button>
                @else
                    <x-actions.button
                        type="submit"
                        iconTrailing="sparkles"
                        loading="profiling.recommendation"
                        loading-target="submitStep"
                        data-test="profiling-finish"
                    >
                        {{ __('Lihat rekomendasi beasiswa') }}
                    </x-actions.button>
                @endif
            </div>
        </form>
    </x-ui.card>
</div>

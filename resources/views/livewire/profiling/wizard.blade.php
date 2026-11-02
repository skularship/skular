<div
    class="mx-auto flex w-full max-w-3xl flex-col gap-6"
    data-test="profiling-wizard"
    {{--
        Temporary frontend state + batch submission.

        Every answer lives in this Alpine object. Moving between steps and
        picking cards never reaches Livewire, the session or the database; the
        whole profile is sent to $wire.submit() once, from the last step, and
        the backend re-validates it before writing it in one transaction.
    --}}
    x-data="{
        steps: @js($this->steps),
        labels: @js($this->stepLabels),
        answers: @js($this->initialAnswers),
        step: @js($this->step),
        storageKey: @js($this->storageKey),
        submitting: false,
        error: '',
        init() {
            this.restore();

            if (! Number.isInteger(this.step) || this.step < 1 || this.step > this.total) {
                this.step = 1;
            }

            this.syncUrl();
        },
        get total() {
            return this.steps.length;
        },
        get currentKey() {
            return this.steps[this.step - 1] || this.steps[0];
        },
        get currentLabel() {
            return this.labels[this.currentKey] || '';
        },
        get isLast() {
            return this.step >= this.total;
        },
        get answeredCount() {
            return this.steps.filter((key) => this.isAnswered(key)).length;
        },
        get progressPercent() {
            return Math.round((this.answeredCount / this.total) * 100);
        },
        isEmpty(value) {
            return value === null || value === undefined || String(value).trim() === '';
        },
        isAnswered(key) {
            return ! this.isEmpty(this.answers[key]);
        },
        requiredMessage(key) {
            const label = this.labels[key];

            return label ? label + ' belum dijawab.' : 'Pertanyaan ini belum dijawab.';
        },
        validateStep(key) {
            const value = this.answers[key];

            if (this.isEmpty(value)) {
                return this.requiredMessage(key);
            }

            if (key === 'age') {
                const age = Number(value);

                if (! Number.isFinite(age) || age < 15 || age > 60) {
                    return 'Usia harus di antara 15 dan 60 tahun.';
                }
            }

            if (key === 'gpa') {
                const gpa = Number(value);

                if (! Number.isFinite(gpa) || gpa < 0 || gpa > 4) {
                    return 'IPK harus di antara 0 dan 4.';
                }
            }

            if (key === 'phone') {
                const phone = String(value).trim();

                if (phone.length < 10 || phone.length > 15) {
                    return 'Nomor telepon harus 10 sampai 15 karakter.';
                }

                if (! /^[0-9+\-\s]+$/.test(phone)) {
                    return 'Nomor telepon hanya boleh berisi angka, tanda +, tanda -, dan spasi.';
                }
            }

            return '';
        },
        jumpTo(number) {
            if (number < 1 || number > this.total) {
                return;
            }

            this.step = number;
            this.syncUrl();
        },
        goTo(number) {
            this.error = '';
            this.jumpTo(number);
        },
        next() {
            const problem = this.validateStep(this.currentKey);

            if (problem) {
                this.error = problem;

                return;
            }

            this.error = '';

            if (this.isLast) {
                this.finish();

                return;
            }

            this.jumpTo(this.step + 1);
        },
        back() {
            this.goTo(this.step - 1);
        },
        onSubmit() {
            if (this.isLast) {
                this.finish();
            } else {
                this.next();
            }
        },
        syncUrl() {
            try {
                const url = new URL(window.location.href);

                if (this.step > 1) {
                    url.searchParams.set('step', String(this.step));
                } else {
                    url.searchParams.delete('step');
                }

                window.history.replaceState({}, '', url);
            } catch (error) {
                // A blocked history API never breaks the wizard.
            }
        },
        payload() {
            const data = {};

            this.steps.forEach((key) => {
                data[key] = this.answers[key] ?? null;
            });

            const sktm = this.answers.has_sktm;

            data.has_sktm = sktm === '1' || sktm === true
                ? true
                : ((sktm === '0' || sktm === false) ? false : null);

            return data;
        },
        finish() {
            if (this.submitting) {
                return;
            }

            for (let index = 0; index < this.steps.length; index++) {
                const problem = this.validateStep(this.steps[index]);

                if (problem) {
                    this.jumpTo(index + 1);
                    this.error = problem;

                    return;
                }
            }

            this.submitting = true;
            this.error = '';

            this.$wire.submit(this.payload()).then((result) => {
                this.submitting = false;

                if (result && result.ok) {
                    this.clearSaved();

                    return;
                }

                if (result && typeof result.step === 'number') {
                    this.jumpTo(result.step);
                }

                this.error = (result && result.message) ? result.message : 'Jawabanmu belum bisa disimpan. Coba lagi ya.';
            }).catch(() => {
                this.submitting = false;
                this.error = 'Koneksi bermasalah. Jawabanmu masih tersimpan di halaman ini, coba kirim ulang.';
            });
        },
        save() {
            try {
                window.sessionStorage.setItem(this.storageKey, JSON.stringify(this.answers));
            } catch (error) {
                // Private mode or a full quota: the in-memory state still works.
            }
        },
        restore() {
            try {
                const saved = JSON.parse(window.sessionStorage.getItem(this.storageKey) || 'null');

                if (! saved || typeof saved !== 'object') {
                    return;
                }

                Object.keys(this.answers).forEach((key) => {
                    if (! Object.prototype.hasOwnProperty.call(saved, key)) {
                        return;
                    }

                    const value = saved[key];

                    if (value === null || value === undefined || String(value).trim() === '') {
                        return;
                    }

                    this.answers[key] = value;
                });
            } catch (error) {
                this.clearSaved();
            }
        },
        clearSaved() {
            try {
                window.sessionStorage.removeItem(this.storageKey);
            } catch (error) {
                // Nothing to clear.
            }
        },
        dotClass(number) {
            if (number < this.step) {
                return 'border-transparent brand-gradient text-brand-ink';
            }

            if (number === this.step) {
                return 'border-brand-orange text-brand-orange ring-2 ring-brand-orange/25';
            }

            return 'border-zinc-200 text-zinc-400 hover:border-brand-orange/50 hover:text-brand-orange dark:border-zinc-600';
        }
    }"
>
    <header class="flex flex-col gap-5 rounded-3xl border border-brand-amber/30 bg-linear-135 from-brand-amber/15 via-white/40 to-brand-teal/5 p-6 sm:p-8 dark:border-brand-amber/20 dark:from-brand-amber/10 dark:via-zinc-900 dark:to-brand-teal/10">
        <x-ui.pill variant="brand" icon="identification">Profiling</x-ui.pill>

        <div>
            <x-display.heading level="1" size="2xl">{{ __('Kenalan dulu, yuk') }}</x-display.heading>
            <x-display.text class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ __('Jawab 8 pertanyaan singkat supaya Skular bisa mencocokkanmu dengan beasiswa yang paling relevan.') }}
            </x-display.text>
        </div>

        <div class="flex items-center gap-4">
            <span class="brand-gradient flex size-12 shrink-0 items-center justify-center rounded-2xl text-base font-bold text-brand-ink shadow-sm" aria-hidden="true" x-text="String(step).padStart(2, '0')">
                {{ str_pad((string) $step, 2, '0', STR_PAD_LEFT) }}
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold tracking-wide text-brand-teal uppercase dark:text-brand-teal-light">
                    {{ __('Langkah') }} <span x-text="step" class="tabular-nums">{{ $step }}</span> {{ __('dari') }} {{ count($this->steps) }}
                </p>
                <p class="text-base leading-snug font-bold break-words text-zinc-900 sm:text-lg dark:text-white" x-text="currentLabel">{{ $this->currentLabel }}</p>
            </div>

            <span class="shrink-0 text-xs font-medium text-zinc-400 tabular-nums" x-text="progressPercent + '%'">{{ $this->progress['percentage'] }}%</span>
        </div>

        <div
            class="h-2 w-full overflow-hidden rounded-full bg-white/60 dark:bg-white/10"
            role="progressbar"
            aria-valuenow="{{ $this->progress['percentage'] }}"
            x-bind:aria-valuenow="progressPercent"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="{{ __('Progres profiling') }}"
        >
            <div
                class="h-2 brand-gradient rounded-full transition-all duration-500"
                style="width: {{ $this->progress['percentage'] }}%"
                x-bind:style="'width: ' + progressPercent + '%'"
            ></div>
        </div>

        <ol class="flex flex-wrap gap-1 sm:gap-1.5" aria-label="{{ __('Progres langkah') }}">
            @foreach ($this->steps as $index => $key)
                @php $number = $index + 1; @endphp

                <li>
                    <button
                        type="button"
                        x-on:click="goTo({{ $number }})"
                        data-test="stepper-dot-{{ $key }}"
                        title="{{ \App\Livewire\Profiling\Wizard::STEP_LABELS[$key] ?? '' }}"
                        class="flex size-7 items-center justify-center rounded-full border text-[0.625rem] font-semibold transition sm:size-6"
                        x-bind:class="dotClass({{ $number }})"
                        aria-current="{{ $number === $step ? 'step' : 'false' }}"
                        x-bind:aria-current="step === {{ $number }} ? 'step' : 'false'"
                        aria-label="{{ __('Langkah :number: :label', ['number' => $number, 'label' => \App\Livewire\Profiling\Wizard::STEP_LABELS[$key] ?? '']) }}"
                    >
                        <x-icons.app-icon name="check" class="size-3" x-show="step > {{ $number }}" x-cloak />
                        <span x-show="step <= {{ $number }}">{{ $number }}</span>
                    </button>
                </li>
            @endforeach
        </ol>
    </header>

    <x-ui.card class="border-brand-teal/15">
        <form class="flex flex-col gap-6" x-on:submit.prevent="onSubmit()" x-on:change="save()">
            {{-- Enter submits the step like the primary button does. --}}
            <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">{{ __('Lanjut') }}</button>

            <fieldset class="m-0 flex min-w-0 flex-col gap-4 border-0 p-0" x-show="currentKey === 'level'" @if ($this->activeStep !== 'level') x-cloak @endif>
                <legend class="mb-3 flex flex-col gap-1">
                    <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Pilih jenjang pendidikanmu saat ini') }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Jenjang ini menentukan jenis beasiswa yang bisa kamu lamar.') }}</span>
                </legend>

                {{-- Short labels: a compact two-column chip grid on phones and
                     four columns once there is room, so the seven levels never
                     turn into a very tall stack. --}}
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->levelOptions as $value => $label)
                        <x-forms.selection-card
                            grouped="single"
                            compact
                            model="answers.level"
                            :value="$value"
                            :title="$label"
                            data-test="option-level-{{ $value }}"
                        />
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col gap-4 border-0 p-0" x-show="currentKey === 'age'" @if ($this->activeStep !== 'age') x-cloak @endif>
                <legend class="sr-only">{{ __('Berapa usiamu sekarang?') }}</legend>

                <x-forms.input
                    name="age"
                    x-model="answers.age"
                    type="number"
                    min="15"
                    max="60"
                    :label="__('Berapa usiamu sekarang?')"
                    :placeholder="__('Contoh: 21')"
                    data-test="input-age"
                />
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col gap-4 border-0 p-0" x-show="currentKey === 'gpa'" @if ($this->activeStep !== 'gpa') x-cloak @endif>
                <legend class="sr-only">{{ __('Berapa IPK terakhirmu?') }}</legend>

                <x-forms.input
                    name="gpa"
                    x-model="answers.gpa"
                    type="number"
                    step="0.01"
                    min="0"
                    max="4"
                    :label="__('Berapa IPK terakhirmu?')"
                    :placeholder="__('Contoh: 3.75')"
                    data-test="input-gpa"
                />
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Gunakan skala 0-4. Kalau belum punya IPK, isi perkiraan terbaikmu.') }}
                </p>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col gap-4 border-0 p-0" x-show="currentKey === 'has_sktm'" @if ($this->activeStep !== 'has_sktm') x-cloak @endif>
                <legend class="flex flex-col gap-1">
                    <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Apakah kamu memiliki SKTM/KHI?') }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sebagian beasiswa mensyaratkan SKTM/KHI. Jawabanmu tidak mempengaruhi statusmu.') }}</span>
                </legend>

                <div class="grid gap-3 sm:grid-cols-2">
                    <x-forms.selection-card
                        grouped="single"
                        model="answers.has_sktm"
                        value="1"
                        :title="__('Ya, saya punya')"
                        :description="__('Punya Surat Keterangan Tidak Mampu atau Kartu Indonesia Sehat.')"
                        icon="document-text"
                        data-test="option-sktm-1"
                    />

                    <x-forms.selection-card
                        grouped="single"
                        model="answers.has_sktm"
                        value="0"
                        :title="__('Belum / tidak ada')"
                        :description="__('Kamu tetap bisa mengajukan banyak beasiswa tanpa dokumen ini.')"
                        icon="x-circle"
                        data-test="option-sktm-0"
                    />
                </div>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col gap-4 border-0 p-0" x-show="currentKey === 'desired_level'" @if ($this->activeStep !== 'desired_level') x-cloak @endif>
                <legend class="flex flex-col gap-1">
                    <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Jenjang apa yang ingin kamu kejar?') }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pilih satu target jenjang pendidikan.') }}</span>
                </legend>

                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->levelOptions as $value => $label)
                        <x-forms.selection-card
                            grouped="single"
                            compact
                            model="answers.desired_level"
                            :value="$value"
                            :title="$label"
                            data-test="option-desired-level-{{ $value }}"
                        />
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col gap-4 border-0 p-0" x-show="currentKey === 'desired_location'" @if ($this->activeStep !== 'desired_location') x-cloak @endif>
                <legend class="flex flex-col gap-1">
                    <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Kamu ingin kuliah di mana?') }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pilih satu lokasi tujuan kuliah.') }}</span>
                </legend>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($this->locationOptions as $value => $label)
                        <x-forms.selection-card
                            grouped="single"
                            model="answers.desired_location"
                            :value="$value"
                            :title="$label"
                            :description="$value === 'dalam_negeri' ? __('Universitas di Indonesia') : __('Universitas di luar negeri')"
                            :icon="$value === 'dalam_negeri' ? 'flag' : 'globe-alt'"
                            data-test="option-desired-location-{{ $value }}"
                        />
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col gap-4 border-0 p-0" x-show="currentKey === 'cluster'" @if ($this->activeStep !== 'cluster') x-cloak @endif>
                <legend class="flex flex-col gap-1">
                    <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Bidang studi yang paling kamu minati') }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pilih satu bidang. Ini menentukan modul pembelajaranmu.') }}</span>
                </legend>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($this->clusters as $clusterOption)
                        <x-forms.selection-card
                            grouped="single"
                            model="answers.cluster"
                            :value="(string) $clusterOption->id"
                            :title="$clusterOption->label"
                            :description="$clusterOption->description"
                            icon="academic-cap"
                            data-test="option-cluster-{{ $clusterOption->id }}"
                        />
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col gap-4 border-0 p-0" x-show="currentKey === 'phone'" @if ($this->activeStep !== 'phone') x-cloak @endif>
                <legend class="sr-only">{{ __('Nomor telepon aktif') }}</legend>

                <x-forms.input
                    name="phone"
                    x-model="answers.phone"
                    type="tel"
                    :label="__('Nomor telepon aktif')"
                    :placeholder="__('Contoh: 081234567890')"
                    autocomplete="tel"
                    data-test="input-phone"
                />
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Kami hanya memakainya untuk mengirim pengingat deadline beasiswamu.') }}
                </p>
            </fieldset>

            @if ($errors->any())
                <x-feedback.callout variant="warning" icon="exclamation-triangle" :heading="__('Periksa lagi ya')">
                    {{ $errors->first() }}
                </x-feedback.callout>
            @endif

            <div
                x-show="error"
                x-cloak
                role="alert"
                class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-950 dark:text-amber-200"
            >
                <span class="font-medium" x-text="error"></span>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-brand-amber/20 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                <div>
                    <x-actions.button type="button" variant="ghost" icon="arrow-left" x-on:click="back()" x-show="step > 1" x-cloak data-test="profiling-back">
                        {{ __('Kembali') }}
                    </x-actions.button>
                </div>

                <x-actions.button
                    type="button"
                    iconTrailing="arrow-right"
                    x-on:click="next()"
                    x-show="! isLast"
                    x-bind:disabled="submitting"
                    data-test="profiling-next"
                >
                    {{ __('Lanjut') }}
                </x-actions.button>

                <x-actions.button
                    type="button"
                    iconTrailing="sparkles"
                    x-on:click="finish()"
                    x-show="isLast"
                    x-cloak
                    x-bind:disabled="submitting"
                    data-test="profiling-finish"
                >
                    <span x-show="! submitting">{{ __('Lihat rekomendasi beasiswa') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('Menyimpan jawaban…') }}</span>
                </x-actions.button>
            </div>

            <p class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                {{ __('Jawabanmu baru disimpan setelah kamu menekan tombol rekomendasi di langkah terakhir.') }}
            </p>
        </form>
    </x-ui.card>
</div>

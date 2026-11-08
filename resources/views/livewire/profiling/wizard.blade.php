{{-- Eight question profiling wizard (App\Livewire\Profiling\Wizard). Answers live in Alpine state and sessionStorage and are submitted once at the end. --}}
<div
    class="mx-auto flex w-full max-w-3xl min-w-0 flex-col gap-5 sm:gap-6"
    data-test="profiling-wizard"
    {{--
        Temporary frontend state + batch submission.

        Every answer lives in this Alpine object. Moving between steps and
        picking cards never reaches Livewire or the database; the draft is
        mirrored to sessionStorage so going back or refreshing the page keeps
        it. The whole profile is sent to $wire.submit() once, from the last
        step, and the backend re-validates it before writing it in one
        transaction.
    --}}
    x-data="{
        steps: @js($this->steps),
        labels: @js($this->stepLabels),
        answers: @js($this->initialAnswers),
        step: @js($this->step),
        storageKey: @js($this->storageKey),
        submitting: false,
        error: '',
        fieldErrors: {},
        announcement: '',
        init() {
            this.restore();

            if (! Number.isInteger(this.step) || this.step < 1 || this.step > this.total) {
                this.step = 1;
            }

            this.syncUrl();
            this.$nextTick(() => this.revealActiveStep());
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
        hasError(key) {
            return Boolean(this.fieldErrors[key]);
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
        setFieldError(key, message) {
            this.fieldErrors = { ...this.fieldErrors, [key]: message };
        },
        revalidate() {
            // Once a field shows an error, re-check it while the mentee types
            // so the message disappears the moment the answer becomes valid.
            const key = this.currentKey;

            if (this.hasError(key)) {
                this.setFieldError(key, this.validateStep(key));
            }
        },
        focusInvalid(key) {
            this.$nextTick(() => {
                const scope = this.$root.querySelector('[data-step=' + key + ']');
                const control = scope ? (scope.querySelector('input:checked') || scope.querySelector('input')) : null;

                if (control) {
                    control.focus({ preventScroll: false });
                }
            });
        },
        jumpTo(number, { focus = true } = {}) {
            if (number < 1 || number > this.total) {
                return;
            }

            this.step = number;
            this.announcement = 'Langkah ' + number + ' dari ' + this.total + ': ' + this.currentLabel;
            this.syncUrl();

            this.$nextTick(() => {
                this.revealActiveStep();

                if (focus) {
                    const heading = document.getElementById('step-heading-' + this.currentKey);

                    if (heading) {
                        heading.focus({ preventScroll: true });
                    }
                }
            });
        },
        revealActiveStep() {
            const active = this.$refs.stepper ? this.$refs.stepper.querySelector('[aria-current=step]') : null;

            if (active && typeof active.scrollIntoView === 'function') {
                active.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
            }
        },
        goTo(number) {
            this.error = '';
            this.jumpTo(number);
        },
        next() {
            const key = this.currentKey;
            const problem = this.validateStep(key);

            this.setFieldError(key, problem);

            if (problem) {
                this.focusInvalid(key);

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
            if (this.step > 1) {
                this.goTo(this.step - 1);
            }
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
                const key = this.steps[index];
                const problem = this.validateStep(key);

                if (problem) {
                    this.jumpTo(index + 1, { focus: false });
                    this.setFieldError(key, problem);
                    this.focusInvalid(key);

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

                const message = (result && result.message) ? result.message : 'Jawabanmu belum bisa disimpan. Coba lagi ya.';

                if (result && typeof result.step === 'number') {
                    this.jumpTo(result.step, { focus: false });
                    this.setFieldError(this.steps[result.step - 1], message);
                    this.focusInvalid(this.steps[result.step - 1]);

                    return;
                }

                this.error = message;
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
        stepState(number) {
            if (number === this.step) {
                return 'current';
            }

            if (this.hasError(this.steps[number - 1])) {
                return 'error';
            }

            return this.isAnswered(this.steps[number - 1]) ? 'done' : 'todo';
        }
    }"
>
    <header class="flex min-w-0 flex-col gap-5 rounded-3xl border border-brand-amber/30 bg-linear-135 from-brand-amber/15 via-white/60 to-brand-teal/5 p-5 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <x-ui.pill variant="brand" icon="identification">Profiling</x-ui.pill>
                <x-display.heading level="1" size="2xl" class="mt-3">{{ __('Kenalan dulu, yuk') }}</x-display.heading>
                <x-display.text class="mt-1 text-zinc-600">
                    {{ __('Jawab 8 pertanyaan singkat supaya Skular bisa mencocokkanmu dengan beasiswa yang paling relevan.') }}
                </x-display.text>
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <div class="flex items-end justify-between gap-3">
                <p class="text-sm font-semibold text-zinc-700">
                    {{ __('Langkah') }}
                    <span class="tabular-nums" x-text="step">{{ $step }}</span>
                    {{ __('dari') }} {{ count($this->steps) }}
                    <span class="text-zinc-400" aria-hidden="true">·</span>
                    <span class="text-brand-teal" x-text="currentLabel">{{ $this->currentLabel }}</span>
                </p>

                <p class="shrink-0 text-xs font-semibold text-zinc-600 tabular-nums">
                    <span x-text="answeredCount">{{ $this->progress['completed'] }}</span>/{{ count($this->steps) }} {{ __('terjawab') }}
                </p>
            </div>

            <div
                class="h-2.5 w-full overflow-hidden rounded-full bg-white/80 ring-1 ring-brand-amber/20"
                role="progressbar"
                aria-valuenow="{{ $this->progress['percentage'] }}"
                x-bind:aria-valuenow="progressPercent"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="{{ __('Progres profiling') }}"
            >
                <div
                    class="h-full rounded-full brand-gradient transition-[width] duration-500 ease-out motion-reduce:transition-none"
                    style="width: {{ $this->progress['percentage'] }}%"
                    x-bind:style="'width: ' + progressPercent + '%'"
                ></div>
            </div>
        </div>

        {{-- Stepper: a horizontally scrollable row of labelled steps on phones
             (the active one is scrolled into view) and an even grid from the
             large breakpoint. Every target is at least 44px tall. --}}
        <nav aria-label="{{ __('Progres langkah') }}" class="-mx-5 min-w-0 sm:-mx-8">
            <ol
                x-ref="stepper"
                class="flex snap-x gap-2 overflow-x-auto scroll-smooth px-5 pb-1 [scrollbar-width:none] sm:px-8 lg:grid lg:grid-cols-8 lg:overflow-visible [&::-webkit-scrollbar]:hidden"
            >
                @foreach ($this->steps as $index => $key)
                    @php
                        $number = $index + 1;
                        $label = \App\Livewire\Profiling\Wizard::STEP_LABELS[$key] ?? '';
                    @endphp

                    <li class="shrink-0 snap-center lg:min-w-0">
                        <button
                            type="button"
                            x-on:click="goTo({{ $number }})"
                            data-test="stepper-dot-{{ $key }}"
                            class="group flex min-h-11 w-full items-center gap-2 rounded-full border px-2.5 py-1.5 text-xs font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-ink lg:flex-col lg:gap-1 lg:rounded-2xl lg:px-1 lg:py-2"
                            x-bind:class="{
                                'border-brand-orange bg-white text-brand-ink shadow-sm ring-2 ring-brand-orange/30': stepState({{ $number }}) === 'current',
                                'border-transparent bg-white/70 text-brand-teal hover:bg-white': stepState({{ $number }}) === 'done',
                                'border-red-300 bg-red-50 text-red-700 hover:bg-red-100': stepState({{ $number }}) === 'error',
                                'border-zinc-200 bg-white/50 text-zinc-600 hover:border-brand-orange/50 hover:text-brand-ink': stepState({{ $number }}) === 'todo',
                            }"
                            aria-current="{{ $number === $step ? 'step' : 'false' }}"
                            x-bind:aria-current="step === {{ $number }} ? 'step' : 'false'"
                            aria-label="{{ __('Langkah :number: :label', ['number' => $number, 'label' => $label]) }}"
                        >
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-full text-[0.6875rem] font-bold transition"
                                x-bind:class="{
                                    'brand-gradient text-brand-ink': stepState({{ $number }}) === 'current' || stepState({{ $number }}) === 'done',
                                    'bg-red-100 text-red-700': stepState({{ $number }}) === 'error',
                                    'bg-zinc-100 text-zinc-600': stepState({{ $number }}) === 'todo',
                                }"
                                aria-hidden="true"
                            >
                                <x-icons.app-icon name="check" class="size-3.5" x-show="stepState({{ $number }}) === 'done'" x-cloak />
                                <span x-show="stepState({{ $number }}) !== 'done'">{{ $number }}</span>
                            </span>
                            <span class="whitespace-nowrap pe-1 lg:max-w-full lg:truncate lg:pe-0">{{ $label }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </nav>

        <p class="sr-only" role="status" aria-live="polite" x-text="announcement"></p>
    </header>

    <x-ui.card padding="none" class="min-w-0 border-brand-teal/15 p-4 sm:p-6">
        <form class="flex flex-col gap-6" x-on:submit.prevent="onSubmit()" x-on:change="save(); revalidate()" x-on:input.debounce.250ms="save(); revalidate()" novalidate>
            {{-- Enter submits the step like the primary button does. --}}
            <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">{{ __('Lanjut') }}</button>

            {{-- Each step slides in on its own; the leaving step hides at once so
                 two questions never overlap, and reduced motion skips the slide. --}}
            @php
                $enter = [
                    'x-transition:enter' => 'transition duration-300 ease-out motion-reduce:transition-none',
                    'x-transition:enter-start' => 'opacity-0 translate-y-3',
                    'x-transition:enter-end' => 'opacity-100 translate-y-0',
                ];
                $legendClass = 'mb-4 flex flex-col gap-1 outline-none';
                $headingClass = 'text-lg font-bold text-zinc-900 outline-none sm:text-xl';
                $hintClass = 'text-sm text-zinc-600';
                $errorClass = 'mt-2 flex items-start gap-1.5 text-sm font-medium text-red-700';
            @endphp

            <fieldset class="m-0 flex min-w-0 flex-col border-0 p-0" data-step="level" x-show="currentKey === 'level'" {{ new \Illuminate\View\ComponentAttributeBag($enter) }} @if ($this->activeStep !== 'level') x-cloak @endif>
                <legend class="{{ $legendClass }}">
                    <span id="step-heading-level" tabindex="-1" class="{{ $headingClass }}">{{ __('Pilih jenjang pendidikanmu saat ini') }}</span>
                    <span class="{{ $hintClass }}">{{ __('Jenjang ini menentukan jenis beasiswa yang bisa kamu lamar.') }}</span>
                </legend>

                <p id="error-level" class="{{ $errorClass }} mt-0! mb-3" role="alert" x-show="hasError('level')" x-cloak>
                    <x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 shrink-0" />
                    <span x-text="fieldErrors.level"></span>
                </p>

                <div role="radiogroup" aria-labelledby="step-heading-level" aria-describedby="error-level" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->levelOptions as $value => $label)
                        <x-forms.selection-card
                            grouped="single"
                            compact
                            model="answers.level"
                            :value="$value"
                            :title="$label"
                            class="min-h-11"
                            data-test="option-level-{{ $value }}"
                        />
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col border-0 p-0" data-step="age" x-show="currentKey === 'age'" {{ new \Illuminate\View\ComponentAttributeBag($enter) }} @if ($this->activeStep !== 'age') x-cloak @endif>
                <legend class="{{ $legendClass }}">
                    <span id="step-heading-age" tabindex="-1" class="{{ $headingClass }}">{{ __('Berapa usiamu sekarang?') }}</span>
                    <span id="hint-age" class="{{ $hintClass }}">{{ __('Beberapa beasiswa punya batas usia pendaftar.') }}</span>
                </legend>

                <x-forms.input
                    name="age"
                    x-model="answers.age"
                    type="number"
                    inputmode="numeric"
                    min="15"
                    max="60"
                    aria-labelledby="step-heading-age"
                    aria-describedby="hint-age error-age"
                    x-bind:aria-invalid="hasError('age') ? 'true' : 'false'"
                    x-bind:class="hasError('age') ? 'border-red-500! focus:ring-red-500!' : ''"
                    class="h-12! sm:max-w-xs"
                    :placeholder="__('Contoh: 21')"
                    data-test="input-age"
                />

                <p id="error-age" class="{{ $errorClass }}" role="alert" x-show="hasError('age')" x-cloak>
                    <x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 shrink-0" />
                    <span x-text="fieldErrors.age"></span>
                </p>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col border-0 p-0" data-step="gpa" x-show="currentKey === 'gpa'" {{ new \Illuminate\View\ComponentAttributeBag($enter) }} @if ($this->activeStep !== 'gpa') x-cloak @endif>
                <legend class="{{ $legendClass }}">
                    <span id="step-heading-gpa" tabindex="-1" class="{{ $headingClass }}">{{ __('Berapa IPK terakhirmu?') }}</span>
                    <span id="hint-gpa" class="{{ $hintClass }}">{{ __('Gunakan skala 0-4. Kalau belum punya IPK, isi perkiraan terbaikmu.') }}</span>
                </legend>

                <x-forms.input
                    name="gpa"
                    x-model="answers.gpa"
                    type="number"
                    inputmode="decimal"
                    step="0.01"
                    min="0"
                    max="4"
                    aria-labelledby="step-heading-gpa"
                    aria-describedby="hint-gpa error-gpa"
                    x-bind:aria-invalid="hasError('gpa') ? 'true' : 'false'"
                    x-bind:class="hasError('gpa') ? 'border-red-500! focus:ring-red-500!' : ''"
                    class="h-12! sm:max-w-xs"
                    :placeholder="__('Contoh: 3.75')"
                    data-test="input-gpa"
                />

                <p id="error-gpa" class="{{ $errorClass }}" role="alert" x-show="hasError('gpa')" x-cloak>
                    <x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 shrink-0" />
                    <span x-text="fieldErrors.gpa"></span>
                </p>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col border-0 p-0" data-step="has_sktm" x-show="currentKey === 'has_sktm'" {{ new \Illuminate\View\ComponentAttributeBag($enter) }} @if ($this->activeStep !== 'has_sktm') x-cloak @endif>
                <legend class="{{ $legendClass }}">
                    <span id="step-heading-has_sktm" tabindex="-1" class="{{ $headingClass }}">{{ __('Apakah kamu memiliki SKTM/KHI?') }}</span>
                    <span class="{{ $hintClass }}">{{ __('Sebagian beasiswa mensyaratkan SKTM/KHI. Jawabanmu tidak mempengaruhi statusmu.') }}</span>
                </legend>

                <p id="error-has_sktm" class="{{ $errorClass }} mt-0! mb-3" role="alert" x-show="hasError('has_sktm')" x-cloak>
                    <x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 shrink-0" />
                    <span x-text="fieldErrors.has_sktm"></span>
                </p>

                <div role="radiogroup" aria-labelledby="step-heading-has_sktm" aria-describedby="error-has_sktm" class="grid gap-3 sm:grid-cols-2">
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

            <fieldset class="m-0 flex min-w-0 flex-col border-0 p-0" data-step="desired_level" x-show="currentKey === 'desired_level'" {{ new \Illuminate\View\ComponentAttributeBag($enter) }} @if ($this->activeStep !== 'desired_level') x-cloak @endif>
                <legend class="{{ $legendClass }}">
                    <span id="step-heading-desired_level" tabindex="-1" class="{{ $headingClass }}">{{ __('Jenjang apa yang ingin kamu kejar?') }}</span>
                    <span class="{{ $hintClass }}">{{ __('Pilih satu target jenjang pendidikan.') }}</span>
                </legend>

                <p id="error-desired_level" class="{{ $errorClass }} mt-0! mb-3" role="alert" x-show="hasError('desired_level')" x-cloak>
                    <x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 shrink-0" />
                    <span x-text="fieldErrors.desired_level"></span>
                </p>

                <div role="radiogroup" aria-labelledby="step-heading-desired_level" aria-describedby="error-desired_level" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->levelOptions as $value => $label)
                        <x-forms.selection-card
                            grouped="single"
                            compact
                            model="answers.desired_level"
                            :value="$value"
                            :title="$label"
                            class="min-h-11"
                            data-test="option-desired-level-{{ $value }}"
                        />
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col border-0 p-0" data-step="desired_location" x-show="currentKey === 'desired_location'" {{ new \Illuminate\View\ComponentAttributeBag($enter) }} @if ($this->activeStep !== 'desired_location') x-cloak @endif>
                <legend class="{{ $legendClass }}">
                    <span id="step-heading-desired_location" tabindex="-1" class="{{ $headingClass }}">{{ __('Preferensi lokasi beasiswa') }}</span>
                    <span class="{{ $hintClass }}">{{ __('Pilih satu lokasi tujuan studi. Pilihan ini dipakai untuk mencocokkan beasiswa, bukan untuk menentukan jenis pendanaan.') }}</span>
                </legend>

                <p id="error-desired_location" class="{{ $errorClass }} mt-0! mb-3" role="alert" x-show="hasError('desired_location')" x-cloak>
                    <x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 shrink-0" />
                    <span x-text="fieldErrors.desired_location"></span>
                </p>

                <div role="radiogroup" aria-labelledby="step-heading-desired_location" aria-describedby="error-desired_location" class="grid gap-3 sm:grid-cols-2">
                    @foreach ($this->locationOptions as $value => $label)
                        <x-forms.selection-card
                            grouped="single"
                            model="answers.desired_location"
                            :value="$value"
                            :title="$label"
                            :description="$value === 'dalam_negeri'
                                ? __('Saya ingin mengikuti program beasiswa untuk studi di Indonesia.')
                                : __('Saya ingin mengikuti program beasiswa untuk studi di luar Indonesia.')"
                            :icon="$value === 'dalam_negeri' ? 'flag' : 'globe-alt'"
                            data-test="option-desired-location-{{ $value }}"
                        />
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="m-0 flex min-w-0 flex-col border-0 p-0" data-step="cluster" x-show="currentKey === 'cluster'" {{ new \Illuminate\View\ComponentAttributeBag($enter) }} @if ($this->activeStep !== 'cluster') x-cloak @endif>
                <legend class="{{ $legendClass }}">
                    <span id="step-heading-cluster" tabindex="-1" class="{{ $headingClass }}">{{ __('Bidang studi yang paling kamu minati') }}</span>
                    <span class="{{ $hintClass }}">{{ __('Pilih satu bidang. Ini menentukan modul pembelajaranmu.') }}</span>
                </legend>

                <p id="error-cluster" class="{{ $errorClass }} mt-0! mb-3" role="alert" x-show="hasError('cluster')" x-cloak>
                    <x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 shrink-0" />
                    <span x-text="fieldErrors.cluster"></span>
                </p>

                <div role="radiogroup" aria-labelledby="step-heading-cluster" aria-describedby="error-cluster" class="grid gap-3 sm:grid-cols-2">
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

            <fieldset class="m-0 flex min-w-0 flex-col border-0 p-0" data-step="phone" x-show="currentKey === 'phone'" {{ new \Illuminate\View\ComponentAttributeBag($enter) }} @if ($this->activeStep !== 'phone') x-cloak @endif>
                <legend class="{{ $legendClass }}">
                    <span id="step-heading-phone" tabindex="-1" class="{{ $headingClass }}">{{ __('Nomor telepon aktif') }}</span>
                    <span id="hint-phone" class="{{ $hintClass }}">{{ __('Kami hanya memakainya untuk mengirim pengingat deadline beasiswamu.') }}</span>
                </legend>

                <x-forms.input
                    name="phone"
                    x-model="answers.phone"
                    type="tel"
                    inputmode="tel"
                    aria-labelledby="step-heading-phone"
                    aria-describedby="hint-phone error-phone"
                    x-bind:aria-invalid="hasError('phone') ? 'true' : 'false'"
                    x-bind:class="hasError('phone') ? 'border-red-500! focus:ring-red-500!' : ''"
                    class="h-12! sm:max-w-sm"
                    :placeholder="__('Contoh: 081234567890')"
                    autocomplete="tel"
                    data-test="input-phone"
                />

                <p id="error-phone" class="{{ $errorClass }}" role="alert" x-show="hasError('phone')" x-cloak>
                    <x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 shrink-0" />
                    <span x-text="fieldErrors.phone"></span>
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
                class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900"
            >
                <span class="font-medium" x-text="error"></span>
            </div>

            {{-- Sticky on phones, right above the app bottom navigation (about 5rem
                 tall below lg), so the primary action is always within reach. --}}
            <div class="sticky bottom-[calc(5rem+env(safe-area-inset-bottom))] z-10 -mx-4 -mb-4 flex items-center justify-between gap-3 rounded-b-3xl border-t border-brand-amber/20 bg-white/95 px-4 py-3 shadow-[0_-12px_24px_-20px_rgba(39,124,147,0.5)] backdrop-blur-sm sm:static sm:mx-0 sm:mb-0 sm:rounded-none sm:bg-transparent sm:px-0 sm:pt-5 sm:pb-0 sm:shadow-none sm:backdrop-blur-none">
                <x-actions.button
                    type="button"
                    variant="outline"
                    icon="arrow-left"
                    x-on:click="back()"
                    x-bind:disabled="step <= 1 || submitting"
                    x-bind:aria-disabled="step <= 1 ? 'true' : 'false'"
                    class="min-h-11 disabled:invisible"
                    data-test="profiling-back"
                >
                    {{ __('Kembali') }}
                </x-actions.button>

                <x-actions.button
                    type="button"
                    iconTrailing="arrow-right"
                    x-on:click="next()"
                    x-show="! isLast"
                    x-bind:disabled="submitting"
                    class="min-h-11 flex-1 sm:flex-none"
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
                    class="min-h-11 flex-1 sm:flex-none"
                    data-test="profiling-finish"
                >
                    <span x-show="! submitting">{{ __('Lihat rekomendasi beasiswa') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('Menyimpan jawaban…') }}</span>
                </x-actions.button>
            </div>
        </form>
    </x-ui.card>

    <p class="px-1 text-xs leading-relaxed text-zinc-600">
        <x-icons.app-icon name="lock-closed" class="me-1 inline size-3.5 align-[-2px] text-brand-teal" />
        {{ __('Jawabanmu tersimpan sementara di perangkat ini saat kamu kembali atau memuat ulang halaman, dan baru disimpan ke profilmu setelah kamu menekan tombol rekomendasi di langkah terakhir.') }}
    </p>
</div>

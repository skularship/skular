{{-- Public Profiling Kilat readiness quiz (App\Livewire\Profiling\QuickAssessment); rule based, nothing is stored. --}}
@php
    use App\Enums\QuickProfilingAsset;
    use App\Enums\QuickProfilingGoal;
    use App\Enums\QuickProfilingStatus;
    use App\Enums\QuickProfilingTimeline;
    use App\Enums\QuickProfilingWeeklyTime;
    use App\Enums\ReadinessCategory;

    $question_count = count($this->questions);
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

        <div class="rounded-3xl border border-white/15 bg-warm-cream p-5 shadow-2xl sm:p-8">
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
                                <circle cx="64" cy="64" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="12" class="text-zinc-200" />
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
                                <span class="text-3xl font-bold text-zinc-900">{{ $result->score }}</span>
                                <span class="text-[0.625rem] font-semibold tracking-wide text-zinc-400 uppercase">/ 100</span>
                            </div>
                        </div>

                        <div>
                            <x-ui.pill :variant="$categoryVariant" :icon="$result->category->icon()">
                                {{ $result->categoryLabel() }}
                            </x-ui.pill>

                            <p class="mt-3 text-lg font-bold text-zinc-900">{{ $result->headline }}</p>
                            <p class="mt-1 text-sm text-zinc-600">{{ $result->subheadline }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-brand-amber/40 bg-brand-amber/10 p-4">
                        <p class="flex items-center gap-2 text-xs font-semibold tracking-wide text-brand-teal uppercase">
                            <x-icons.app-icon name="exclamation-triangle" class="size-4" />
                            {{ __('Hambatan utama') }}
                        </p>
                        <p class="mt-1.5 text-sm text-zinc-700">{{ $result->blocker }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold tracking-wide text-brand-teal uppercase">
                            {{ __('Tiga langkah berikutnya') }}
                        </p>

                        <ol class="mt-3 flex flex-col gap-2.5">
                            @foreach ($result->recommendations as $index => $recommendation)
                                <li class="flex items-start gap-3 rounded-xl border border-zinc-200/80 bg-white/70 p-3">
                                    <span class="brand-gradient mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold text-brand-ink" aria-hidden="true">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-sm text-zinc-700">{{ $recommendation }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    <div class="rounded-2xl border border-brand-teal/20 bg-brand-teal/5 p-4">
                        <p class="flex items-center gap-2 text-xs font-semibold tracking-wide text-brand-teal uppercase">
                            <x-icons.app-icon name="calendar-days" class="size-4" />
                            {{ __('Ringkasan waktu') }}
                        </p>
                        <p class="mt-1.5 text-sm text-zinc-700">{{ $result->timeline_summary }}</p>
                    </div>

                    <div class="rounded-2xl border border-brand-teal/15 bg-linear-135 from-brand-amber/20 via-white/40 to-brand-teal/10 p-5">
                        <p class="text-base font-bold text-zinc-900">{{ __('Bangun Kesiapanmu Bersama Skular') }}</p>
                        <p class="mt-1 text-sm text-zinc-600">
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

                    <p class="text-xs leading-relaxed text-zinc-500">
                        {{ __('Skor ini adalah indikator kesiapan persiapan, bukan peluang lolos beasiswa. Hasil dan jawabanmu tidak disimpan di akun mana pun.') }}
                    </p>
                </div>
            @else
                {{--
                    Temporary frontend state + batch submission.

                    Every answer stays in this Alpine object. Choosing an option
                    and moving between questions never reaches the server, so the
                    interaction is instant and no database write can happen while
                    the visitor is still answering. The whole batch is handed to
                    $wire.submit() only from finish(), and the backend re-validates
                    it before scoring.
                --}}
                <form
                    class="flex flex-col gap-5"
                    data-test="quick-form"
                    x-data="{
                        questions: @js($this->questions),
                        answers: @js($this->initialAnswers),
                        step: 1,
                        submitting: false,
                        error: '',
                        storageKey: @js(\App\Livewire\Profiling\QuickAssessment::STORAGE_KEY),
                        none: @js(QuickProfilingAsset::None->value),
                        init() {
                            this.restore();

                            const unanswered = this.questions.findIndex((question) => ! this.isAnswered(question.field));

                            this.step = unanswered === -1 ? this.total : unanswered + 1;
                        },
                        get total() {
                            return this.questions.length;
                        },
                        get current() {
                            return this.questions[this.step - 1] || this.questions[0];
                        },
                        get percent() {
                            return Math.round((this.step / this.total) * 100);
                        },
                        isAnswered(field) {
                            const value = this.answers[field];

                            if (Array.isArray(value)) {
                                return value.length > 0;
                            }

                            return typeof value === 'string' && value.trim() !== '';
                        },
                        toggleAsset(value) {
                            const picked = Array.isArray(this.answers.assets) ? [...this.answers.assets] : [];

                            if (value === this.none) {
                                this.answers.assets = picked.includes(value) ? [] : [value];
                            } else {
                                const withoutNone = picked.filter((item) => item !== this.none);

                                this.answers.assets = withoutNone.includes(value)
                                    ? withoutNone.filter((item) => item !== value)
                                    : withoutNone.concat([value]);
                            }

                            this.error = '';
                            this.save();
                        },
                        goTo(number) {
                            if (number < 1 || number > this.total) {
                                return;
                            }

                            this.error = '';
                            this.step = number;
                        },
                        next() {
                            if (! this.isAnswered(this.current.field)) {
                                this.error = 'Pilih satu jawaban dulu sebelum lanjut.';

                                return;
                            }

                            if (this.step >= this.total) {
                                this.finish();

                                return;
                            }

                            this.goTo(this.step + 1);
                        },
                        back() {
                            this.goTo(this.step - 1);
                        },
                        finish() {
                            if (this.submitting) {
                                return;
                            }

                            const unanswered = this.questions.findIndex((question) => ! this.isAnswered(question.field));

                            if (unanswered !== -1) {
                                this.step = unanswered + 1;
                                this.error = 'Masih ada pertanyaan yang belum dijawab.';

                                return;
                            }

                            this.submitting = true;
                            this.error = '';

                            this.$wire.submit(this.answers).then((result) => {
                                this.submitting = false;

                                if (result && result.ok) {
                                    this.clearSaved();

                                    return;
                                }

                                if (result && typeof result.step === 'number') {
                                    this.step = result.step;
                                }

                                this.error = (result && result.message) ? result.message : 'Jawabanmu belum bisa diproses. Coba lagi ya.';
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

                                    if (value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0)) {
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
                        }
                    }"
                    x-on:submit.prevent="next()"
                    x-on:change="save()"
                >
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold tracking-wide text-brand-teal uppercase">
                            <span>
                                {{ __('Pertanyaan') }}
                                <span x-text="step" class="tabular-nums">{{ $question_count > 0 ? 1 : 0 }}</span>
                                {{ __('dari') }} {{ $question_count }}
                            </span>
                            <span x-text="percent + '%'" class="tabular-nums">{{ (int) round(1 / max(1, $question_count) * 100) }}%</span>
                        </div>

                        <div
                            class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-200/80"
                            role="progressbar"
                            aria-valuenow="{{ (int) round(1 / max(1, $question_count) * 100) }}"
                            x-bind:aria-valuenow="percent"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="{{ __('Progres cek kesiapan') }}"
                        >
                            <div
                                class="h-2 brand-gradient rounded-full transition-all duration-500 motion-reduce:transition-none"
                                style="width: {{ (int) round(1 / max(1, $question_count) * 100) }}%"
                                x-bind:style="'width: ' + percent + '%'"
                            ></div>
                        </div>
                    </div>

                    @foreach ($this->questions as $index => $question)
                        @php $number = $index + 1; @endphp

                        <fieldset
                            x-show="step === {{ $number }}"
                            @if ($number > 1) x-cloak @endif
                            class="m-0 min-w-0 border-0 p-0"
                        >
                            <legend class="block">
                                <span class="block text-lg font-bold text-zinc-900">{{ __($question['title']) }}</span>
                                <span class="mt-1 block text-sm text-zinc-500">{{ __($question['description']) }}</span>
                            </legend>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @switch($question['field'])
                                    @case('currentStatus')
                                        @foreach (QuickProfilingStatus::cases() as $option)
                                            <x-forms.selection-card
                                                grouped="single"
                                                model="answers.currentStatus"
                                                :value="$option->value"
                                                :title="$option->getLabel()"
                                                :description="$option->description()"
                                                :icon="$option->icon()"
                                                data-test="option-status-{{ $option->value }}"
                                            />
                                        @endforeach
                                        @break

                                    @case('target')
                                        @foreach (QuickProfilingGoal::cases() as $option)
                                            <x-forms.selection-card
                                                grouped="single"
                                                model="answers.target"
                                                :value="$option->value"
                                                :title="$option->getLabel()"
                                                :description="$option->description()"
                                                :icon="$option->icon()"
                                                data-test="option-target-{{ $option->value }}"
                                            />
                                        @endforeach
                                        @break

                                    @case('registerTimeline')
                                        @foreach (QuickProfilingTimeline::cases() as $option)
                                            <x-forms.selection-card
                                                grouped="single"
                                                model="answers.registerTimeline"
                                                :value="$option->value"
                                                :title="$option->getLabel()"
                                                :icon="$option->icon()"
                                                data-test="option-timeline-{{ $option->value }}"
                                            />
                                        @endforeach
                                        @break

                                    @case('assets')
                                        <div class="grid gap-3 sm:col-span-2 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach (QuickProfilingAsset::cases() as $option)
                                                <x-forms.selection-card
                                                    grouped="multi"
                                                    model="answers.assets"
                                                    change="toggleAsset"
                                                    :value="$option->value"
                                                    :title="$option->getLabel()"
                                                    :icon="$option->icon()"
                                                    data-test="option-asset-{{ $option->value }}"
                                                />
                                            @endforeach
                                        </div>
                                        @break

                                    @case('weeklyPrepTime')
                                        @foreach (QuickProfilingWeeklyTime::cases() as $option)
                                            <x-forms.selection-card
                                                grouped="single"
                                                model="answers.weeklyPrepTime"
                                                :value="$option->value"
                                                :title="$option->getLabel()"
                                                :icon="$option->icon()"
                                                data-test="option-weekly-{{ $option->value }}"
                                            />
                                        @endforeach
                                        @break
                                @endswitch
                            </div>
                        </fieldset>
                    @endforeach

                    @if ($errors->any())
                        <x-feedback.callout variant="warning" icon="exclamation-triangle" :heading="__('Periksa lagi ya')">
                            {{ $errors->first() }}
                        </x-feedback.callout>
                    @endif

                    <div
                        x-show="error"
                        x-cloak
                        role="alert"
                        class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"
                    >
                        <span class="font-medium" x-text="error"></span>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200/80 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <x-actions.button type="button" variant="ghost" size="lg" icon="arrow-left" x-on:click="back()" x-show="step > 1" x-cloak data-test="quick-back">
                                {{ __('Kembali') }}
                            </x-actions.button>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <x-actions.button
                                type="button"
                                size="lg"
                                iconTrailing="arrow-right"
                                x-on:click="next()"
                                x-show="step < total"
                                x-bind:disabled="! isAnswered(current.field) || submitting"
                                data-test="quick-next"
                            >
                                {{ __('Lanjut') }}
                            </x-actions.button>

                            <x-actions.button
                                type="button"
                                size="lg"
                                iconTrailing="sparkles"
                                x-on:click="finish()"
                                x-show="step >= total"
                                x-cloak
                                x-bind:disabled="submitting"
                                data-test="quick-submit"
                            >
                                <span x-show="! submitting">{{ __('Lihat Hasil Kesiapan') }}</span>
                                <span x-show="submitting" x-cloak>{{ __('Menghitung hasil…') }}</span>
                            </x-actions.button>
                        </div>
                    </div>

                    <p class="text-xs leading-relaxed text-zinc-500">
                        {{ __('Jawabanmu baru dikirim saat kamu menekan tombol hasil di pertanyaan terakhir.') }}
                    </p>
                </form>
            @endif
        </div>
    </div>
</section>

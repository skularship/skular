{{-- Try Out list (App\Livewire\Tryout\Index): hero, then one card per published Try Out with its four subtests,
     total duration, labels (Versi uji / Pro) and the mentee's attempt status. Data: cards (tryout, attempt, in_progress). --}}
<div class="mx-auto flex w-full max-w-5xl flex-col gap-8">
    <section class="relative overflow-hidden rounded-4xl bg-brand-teal-deep px-6 py-8 text-white shadow-lift sm:px-10 sm:py-10" data-tour="tryout-hero">
        <div class="pointer-events-none absolute -end-16 -top-16 size-64 rounded-full bg-brand-teal-light/30 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-20 end-24 size-56 rounded-full bg-brand-amber/25 blur-2xl"></div>

        <div class="relative max-w-2xl">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur">
                <x-icons.app-icon name="academic-cap" class="size-3.5" />
                {{ __('Simulasi tes bahasa') }}
            </span>
            <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Try Out IELTS') }}</h1>
            <p class="mt-3 text-base text-white/85">
                {{ __('Ukur kemampuan bahasa Inggrismu lewat empat subtes. Soal Listening dan Reading menyesuaikan jawabanmu, Writing dan Speaking dinilai AI. Skor dan level bisa kamu lihat gratis.') }}
            </p>

            <div class="mt-6 flex flex-wrap gap-2">
                @foreach (\App\Enums\TryoutSkill::cases() as $skill)
                    <span class="inline-flex items-center gap-2 rounded-2xl bg-white/10 px-3 py-2 text-sm font-semibold ring-1 ring-white/15">
                        <x-icons.app-icon :name="$skill->icon()" class="size-4 text-brand-amber" />
                        {{ $skill->getLabel() }}
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    @if ($cards->isEmpty())
        <x-ui.empty-state
            icon="academic-cap"
            :title="__('Belum ada Try Out')"
            :description="__('Try Out baru akan muncul di sini. Cek lagi nanti ya.')"
            data-test="tryout-empty"
        />
    @else
        <div class="grid gap-5 md:grid-cols-2" data-test="tryout-list">
            @foreach ($cards as $card)
                @php
                    $tryout = $card['tryout'];
                    $attempt = $card['attempt'];
                @endphp

                <x-ui.card padding="none" hover class="flex flex-col overflow-hidden" wire:key="tryout-{{ $tryout->id }}" data-test="tryout-card">
                    <div class="flex flex-col gap-4 p-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.pill variant="info">{{ $tryout->exam_type->getLabel() }}</x-ui.pill>
                            @if ($tryout->is_trial)
                                <x-ui.pill variant="warning">{{ __('Versi uji') }}</x-ui.pill>
                            @endif
                            @if ($tryout->requires_pro)
                                <x-ui.pill variant="peach" icon="star">{{ __('Pro') }}</x-ui.pill>
                            @endif
                            @if ($attempt?->isCompleted())
                                <x-ui.pill variant="success" icon="check-badge">{{ __('Sudah dikerjakan') }}</x-ui.pill>
                            @elseif ($card['in_progress'])
                                <x-ui.pill variant="brand" icon="clock">{{ __('Sedang dikerjakan') }}</x-ui.pill>
                            @endif
                        </div>

                        <div>
                            <h2 class="text-xl font-extrabold text-zinc-900">{{ $tryout->title }}</h2>
                            <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500">
                                <x-icons.app-icon name="clock" class="size-4" />
                                {{ __('Sekitar :minutes menit', ['minutes' => $tryout->totalMinutes()]) }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ($tryout->sections as $section)
                                <div class="rounded-2xl bg-brand-cream px-3 py-2.5">
                                    <x-icons.app-icon :name="$section->skill->icon()" class="size-4 text-brand-teal" />
                                    <p class="mt-1 text-sm font-bold text-zinc-800">{{ $section->skill->getLabel() }}</p>
                                    <p class="text-xs text-zinc-500">{{ $section->durationLabel() }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if ($attempt?->isCompleted() && $attempt->overall_band !== null)
                            <p class="rounded-2xl bg-brand-mint-soft px-4 py-3 text-sm text-emerald-800">
                                {{ __('Hasil terakhirmu: band') }} <strong class="text-base">{{ number_format($attempt->overall_band, 1, ',', '') }}</strong>
                                <span class="text-emerald-700/80">({{ __('estimasi latihan') }})</span>
                            </p>
                        @endif
                    </div>

                    <div class="mt-auto flex flex-wrap gap-2 border-t border-brand-teal/10 bg-brand-cream/60 p-4">
                        @if ($card['in_progress'])
                            <x-actions.link-button :href="route('tryout.runner', $attempt)" variant="teal" size="lg" icon="play" class="flex-1">
                                {{ __('Lanjutkan') }}
                            </x-actions.link-button>
                        @else
                            <x-actions.link-button :href="route('tryout.show', $tryout)" wire:navigate variant="teal" size="lg" icon="play" class="flex-1">
                                {{ $attempt ? __('Lihat detail') : __('Mulai Try Out') }}
                            </x-actions.link-button>
                        @endif

                        @if ($attempt?->isCompleted())
                            <x-actions.link-button :href="route('tryout.result', $attempt)" wire:navigate variant="outline" size="lg" icon="chart-bar">
                                {{ __('Lihat hasil') }}
                            </x-actions.link-button>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    <p class="text-center text-xs text-zinc-500">{{ config('tryout.disclaimer') }}</p>

    <livewire:onboarding.product-tour tour="tryout" />
</div>

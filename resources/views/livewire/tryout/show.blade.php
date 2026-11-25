{{-- Try Out intro (App\Livewire\Tryout\Show): subtest cards, mode choice (Latihan / Ujian), device checklist,
     disclaimer and the start button. Resumes an attempt in progress.
     Data: tryout (with sections), active_attempt, latest_completed, blocked_reason, modes. --}}
<div class="mx-auto grid w-full max-w-6xl gap-6 lg:grid-cols-[1fr_22rem]">
    <div class="flex flex-col gap-6">
        <a href="{{ route('tryout.index') }}" wire:navigate class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-zinc-500 hover:text-brand-teal">
            <x-icons.app-icon name="arrow-left" class="size-4" />
            {{ __('Semua Try Out') }}
        </a>

        <div>
            <div class="flex flex-wrap gap-2">
                <x-ui.pill variant="info">{{ $tryout->exam_type->getLabel() }}</x-ui.pill>
                @if ($tryout->is_trial)
                    <x-ui.pill variant="warning">{{ __('Versi uji') }}</x-ui.pill>
                @endif
                @if ($tryout->requires_pro)
                    <x-ui.pill variant="peach" icon="star">{{ __('Khusus Pro') }}</x-ui.pill>
                @endif
            </div>

            <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl">{{ $tryout->title }}</h1>

            @if (filled($tryout->description))
                <div class="mt-3 max-w-2xl text-base text-zinc-600">
                    <x-learning.rich-content :content="$tryout->description" />
                </div>
            @else
                <p class="mt-3 max-w-2xl text-base text-zinc-600">
                    {{ __('Kerjakan empat subtes berurutan. Soal Listening dan Reading menyesuaikan kemampuanmu: jawab benar, soal berikutnya lebih sulit.') }}
                </p>
            @endif
        </div>

        <ol class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" data-test="tryout-sections">
            @foreach ($tryout->sections as $section)
                <li class="relative overflow-hidden rounded-3xl border border-brand-teal/10 bg-white p-5 shadow-soft">
                    <span class="absolute end-4 top-4 flex size-9 items-center justify-center rounded-2xl bg-brand-teal-soft text-brand-teal">
                        <x-icons.app-icon :name="$section->skill->icon()" class="size-5" />
                    </span>
                    <p class="text-xs font-extrabold text-brand-teal">{{ $loop->iteration }}</p>
                    <p class="mt-1 text-lg font-extrabold text-zinc-900">{{ $section->skill->getLabel() }}</p>
                    <p class="mt-1 text-sm text-zinc-600">{{ $section->summaryLabel() }}</p>
                    <p class="text-sm text-zinc-600">{{ $section->durationLabel() }}</p>
                </li>
            @endforeach
        </ol>

        @if (filled($tryout->instructions))
            <x-ui.card padding="md">
                <h2 class="text-base font-bold text-zinc-900">{{ __('Aturan umum') }}</h2>
                <div class="mt-2 text-sm text-zinc-600">
                    <x-learning.rich-content :content="$tryout->instructions" />
                </div>
            </x-ui.card>
        @endif

        @unless ($active_attempt)
            <div>
                <h2 class="text-lg font-extrabold text-zinc-900">{{ __('Pilih mode') }}</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="{{ __('Mode Try Out') }}">
                    @foreach ($modes as $option)
                        @php $is_selected = $mode === $option->value; @endphp
                        <button
                            type="button"
                            role="radio"
                            aria-checked="{{ $is_selected ? 'true' : 'false' }}"
                            wire:click="$set('mode', '{{ $option->value }}')"
                            data-test="mode-{{ $option->value }}"
                            @class([
                                'flex flex-col gap-2 rounded-3xl border-2 p-5 text-start transition',
                                'border-brand-teal bg-brand-teal-soft shadow-soft' => $is_selected,
                                'border-brand-cream-deep bg-white hover:border-brand-teal/40' => ! $is_selected,
                            ])
                        >
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-base font-extrabold text-zinc-900">{{ $option->getLabel() }}</span>
                                <span @class([
                                    'flex size-5 items-center justify-center rounded-full border-2',
                                    'border-brand-teal' => $is_selected,
                                    'border-zinc-300' => ! $is_selected,
                                ])>
                                    @if ($is_selected)
                                        <span class="size-2.5 rounded-full bg-brand-teal"></span>
                                    @endif
                                </span>
                            </span>
                            <span class="text-sm text-zinc-600">{{ $option->description() }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endunless
    </div>

    <aside class="lg:pt-10">
        <div class="sticky top-6 flex flex-col gap-4 rounded-4xl border border-brand-teal/10 bg-white p-6 shadow-lift" x-data="{ headset: false, mic: false, network: false, speech: !! (window.SpeechRecognition || window.webkitSpeechRecognition) }">
            @if ($active_attempt)
                <h2 class="text-lg font-extrabold text-zinc-900">{{ __('Lanjutkan Try Out') }}</h2>
                <p class="text-sm text-zinc-600">{{ __('Kamu masih punya Try Out yang belum selesai. Waktunya tetap berjalan dari server, jadi lanjutkan sekarang ya.') }}</p>
                <x-actions.link-button :href="route('tryout.runner', $active_attempt)" variant="teal" size="lg" icon="play" class="w-full" data-test="resume-tryout">
                    {{ __('Lanjutkan') }}
                </x-actions.link-button>
            @else
                <h2 class="text-lg font-extrabold text-zinc-900">{{ __('Sebelum mulai') }}</h2>

                <div class="flex flex-col gap-3 text-sm text-zinc-700">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" x-model="headset" class="mt-0.5 size-4 rounded border-zinc-300 text-brand-teal focus:ring-brand-teal">
                        {{ __('Headset terpasang, audio terdengar') }}
                    </label>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" x-model="mic" class="mt-0.5 size-4 rounded border-zinc-300 text-brand-teal focus:ring-brand-teal">
                        <span>
                            {{ __('Mikrofon diizinkan (untuk Speaking)') }}
                            <button
                                type="button"
                                class="ms-1 font-semibold text-brand-teal underline"
                                x-on:click="navigator.mediaDevices?.getUserMedia({ audio: true }).then((stream) => { stream.getTracks().forEach((t) => t.stop()); mic = true; }).catch(() => { mic = false; alert('{{ __('Mikrofon belum diizinkan. Izinkan di pengaturan browser ya.') }}'); })"
                            >{{ __('Tes') }}</button>
                        </span>
                    </label>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" x-model="network" class="mt-0.5 size-4 rounded border-zinc-300 text-brand-teal focus:ring-brand-teal">
                        {{ __('Koneksi stabil sekitar :hours jam', ['hours' => max(1, (int) ceil($tryout->totalMinutes() / 60))]) }}
                    </label>
                </div>

                <p class="text-sm text-zinc-500">{{ __('Waktu tiap subtes berjalan dari server. Kalau koneksi putus, kamu bisa lanjut dari soal terakhir.') }}</p>

                <p x-show="! speech" x-cloak class="rounded-2xl bg-brand-amber-soft px-4 py-3 text-sm text-amber-900" data-test="speech-warning">
                    {{ __('Browser ini belum bisa mengenali suara untuk Speaking. Kerjakan Try Out di Google Chrome atau Microsoft Edge (laptop atau Android) ya.') }}
                </p>

                @if ($blocked_reason)
                    <p class="rounded-2xl bg-brand-amber-soft px-4 py-3 text-sm text-amber-900" data-test="tryout-blocked">{{ $blocked_reason }}</p>
                    @if (config('pricing.enabled'))
                        <x-actions.link-button :href="route('premium')" variant="primary" size="lg" icon="star" class="w-full">
                            {{ __('Lihat paket Pro') }}
                        </x-actions.link-button>
                    @endif
                @else
                    <button
                        type="button"
                        wire:click="start"
                        wire:loading.attr="disabled"
                        x-bind:disabled="! (headset && network)"
                        class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-brand-teal text-base font-extrabold text-white shadow-soft transition hover:bg-brand-teal-deep disabled:cursor-not-allowed disabled:opacity-50"
                        data-test="start-tryout"
                    >
                        <span wire:loading.remove wire:target="start">{{ __('Mulai :skill', ['skill' => $tryout->sections->first()?->skill->getLabel() ?? '']) }}</span>
                        <span wire:loading wire:target="start">{{ __('Menyiapkan soal...') }}</span>
                    </button>
                    <p class="-mt-2 text-center text-xs text-zinc-500" x-show="! (headset && network)">{{ __('Centang headset dan koneksi dulu ya.') }}</p>
                @endif

                @error('start')
                    <p class="text-sm font-semibold text-red-600">{{ $message }}</p>
                @enderror
            @endif

            @if ($latest_completed)
                <a href="{{ route('tryout.result', $latest_completed) }}" wire:navigate class="text-center text-sm font-semibold text-brand-teal hover:underline">
                    {{ __('Lihat hasil terakhirmu') }}
                </a>
            @endif

            <p class="text-xs text-zinc-500">{{ config('tryout.disclaimer') }}</p>
        </div>
    </aside>
</div>

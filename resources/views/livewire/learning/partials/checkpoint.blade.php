@php
    $submitted_forms = $forms->filter(fn ($form) => $responses->has($form->id))->count();
    $score_display = $average_score !== null && $average_score > 0 ? (string) $average_score : '—';
@endphp

<div class="sk-rise flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-brand-amber/25 px-2.5 py-1 text-xs font-bold text-brand-teal dark:text-brand-amber">
            <x-icons.app-icon name="flag" class="size-3.5" />
            {{ __('Checkpoint') }}
        </span>
        <h2 class="text-xl font-extrabold tracking-tight text-zinc-900 dark:text-white">{{ __('Kunci progresmu') }}</h2>
        <p class="max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Checkpoint dicatat setelah kamu mengonfirmasi sendiri. Modul berikutnya baru terbuka setelah konfirmasi ini.') }}
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat
            icon="beaker"
            tone="peach"
            :label="__('Latihan praktik')"
            :value="$submitted_forms.'/'.$forms->count()"
            :hint="__('form terkirim')"
        />

        <x-ui.stat
            icon="sparkles"
            tone="teal"
            :label="__('Rata-rata skor AI')"
            :value="$score_display"
            :hint="__('dari feedback AI')"
        />

        <x-ui.stat
            icon="check-badge"
            :tone="$module_completed ? 'mint' : 'amber'"
            :label="__('Status checkpoint')"
            :value="$module_completed ? __('Selesai') : __('Belum dikunci')"
            :hint="$module_completed ? __('modul selesai') : __('konfirmasi manual')"
        />
    </div>

    @if (! $module_completed)
        <x-ui.card variant="amber" class="flex flex-col gap-4">
            <div class="flex items-start gap-4">
                <x-ui.icon-badge icon="clipboard-document-check" tone="amber" size="lg" solid />

                <div>
                    <p class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Sudah puas dengan hasil praktikmu?') }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ __('Kamu bisa mengulang latihan kapan saja sebelum mengunci checkpoint. Setelah dikunci, modul berikutnya terbuka.') }}
                    </p>
                </div>
            </div>

            @error('checkpoint')
                <x-feedback.callout variant="warning" :heading="__('Latihan praktik belum lengkap')" data-test="checkpoint-blocked">
                    {{ $message }}
                </x-feedback.callout>
            @enderror

            <div class="flex flex-wrap gap-3">
                @if ($practice_pending === 0)
                    <x-actions.button
                        type="button"
                        icon="check-badge"
                        wire:click="confirmCheckpoint"
                        loading="module.checkpoint"
                        data-test="confirm-checkpoint"
                    >
                        {{ __('Tandai modul selesai') }}
                    </x-actions.button>
                @else
                    <x-actions.button type="button" icon="beaker" wire:click="goToSection('praktik')" loading="module.section">
                        {{ trans_choice('Kerjakan :count latihan dulu|Kerjakan :count latihan dulu', $practice_pending, ['count' => $practice_pending]) }}
                    </x-actions.button>
                @endif

                <x-actions.button type="button" variant="outline" icon="arrow-left" wire:click="previousSection" loading="module.section">
                    {{ __('Kembali ke praktik') }}
                </x-actions.button>
            </div>
        </x-ui.card>
    @else
        <div class="relative overflow-hidden rounded-4xl border border-emerald-200 bg-linear-to-br from-emerald-50 to-brand-amber/15 p-6 shadow-soft dark:border-emerald-500/30 dark:from-emerald-950/60 dark:to-brand-amber/5" data-test="module-completed">
            <div class="pointer-events-none absolute -top-16 -right-10 size-48 rounded-full bg-brand-amber/25 blur-3xl" aria-hidden="true"></div>

            <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <span class="sk-pop flex size-14 shrink-0 items-center justify-center rounded-2xl brand-gradient text-brand-ink shadow-pop">
                        <x-icons.app-icon name="trophy" class="size-7" />
                    </span>

                    <div>
                        <p class="text-lg font-extrabold text-emerald-700 dark:text-emerald-300">{{ __('Modul selesai, bagus sekali! 🎉') }}</p>
                        <p class="mt-1 text-sm text-emerald-700/90 dark:text-emerald-300/90">
                            @if ($next_module)
                                {{ __('Modul ":title" sudah terbuka untukmu.', ['title' => $next_module->title]) }}
                            @else
                                {{ __('Ini modul terakhirmu. Lanjutkan ke pendaftaran beasiswamu ya!') }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if ($next_module && $next_module->isUnlocked())
                        <x-actions.button type="button" iconTrailing="arrow-right" wire:navigate href="{{ route('modules.show', $next_module->id) }}" data-test="next-module">
                            {{ __('Lanjut modul berikutnya') }}
                        </x-actions.button>
                    @endif

                    <x-actions.link-button :href="route('dashboard')" wire:navigate variant="outline" icon="map">
                        {{ __('Lihat roadmap') }}
                    </x-actions.link-button>
                </div>
            </div>
        </div>
    @endif

    <x-ui.card variant="teal" class="flex flex-col gap-5">
        <div class="flex items-start gap-3">
            <x-ui.icon-badge icon="chat-bubble-left-right" tone="teal" />
            <div>
                <p class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Gimana modulnya?') }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Bantu kami membuat modul berikutnya lebih baik. Centang yang kamu rasakan, lalu tulis saranmu.') }}
                </p>
            </div>
        </div>

        <fieldset class="flex flex-col gap-2">
            <legend class="sr-only">{{ __('Pilih yang berlaku untukmu') }}</legend>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($rating_options as $option)
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-zinc-200 bg-white/80 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-brand-teal/50 has-[:checked]:border-brand-teal has-[:checked]:bg-brand-teal/8 has-[:checked]:text-brand-teal dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300 dark:has-[:checked]:bg-brand-teal/15 dark:has-[:checked]:text-brand-teal-light">
                        <input type="checkbox" wire:model="ratings" value="{{ $option }}" class="size-4 rounded border-zinc-300 text-brand-teal focus:ring-brand-teal">
                        {{ $option }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="flex flex-col gap-1.5">
            <label for="module-suggestion" class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                {{ __('Saran atau keluhanmu (opsional)') }}
            </label>
            <textarea
                id="module-suggestion"
                wire:model="suggestion"
                rows="3"
                placeholder="{{ __('Contoh: penjelasan bagian kedua masih terasa cepat.') }}"
                class="block w-full rounded-xl border border-zinc-200 bg-white px-3.5 py-3 text-base text-zinc-700 shadow-none transition focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-accent focus:ring-offset-accent-foreground sm:text-sm dark:border-zinc-600 dark:bg-white/10 dark:text-zinc-300"
            ></textarea>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-brand-teal/10 pt-4 dark:border-white/10">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Masukanmu bersifat anonim di laporan admin.') }}</p>

            <x-actions.button
                type="button"
                icon="paper-airplane"
                wire:click="saveModuleFeedback"
                loading="module.feedback"
                data-test="save-feedback"
            >
                {{ __('Kirim feedback modul') }}
            </x-actions.button>
        </div>
    </x-ui.card>
</div>

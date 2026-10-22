<div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <header class="flex flex-col gap-4">
        <a
            href="{{ route('scholarships.tracker') }}"
            wire:navigate
            class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-zinc-500 transition hover:text-brand-teal dark:text-zinc-400 dark:hover:text-brand-teal-light"
        >
            <x-icons.app-icon name="arrow-left" class="size-4" />
            {{ __('Beasiswa Saya') }}
        </a>

        <div class="relative overflow-hidden rounded-4xl brand-gradient-warm p-6 text-brand-ink shadow-pop sm:p-8">
            <div class="pointer-events-none absolute -top-16 -right-10 size-48 rounded-full bg-white/30 blur-3xl" aria-hidden="true"></div>

            <div class="relative z-10 flex flex-col gap-3">
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-brand-ink/10 px-3 py-1 text-xs font-bold tracking-wide text-brand-ink uppercase">
                    <x-icons.app-icon name="sparkles" class="size-3.5" />
                    {{ __('Twibbon') }}
                </span>

                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Rayakan kelulusanmu!') }}</h1>
                <p class="max-w-2xl text-sm text-brand-ink/80">
                    {{ __('Buat twibbon dengan namamu dan beasiswa yang kamu raih, lalu bagikan ke teman dan keluargamu.') }}
                </p>

                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-ink/10 px-3 py-1.5 text-xs font-bold text-brand-ink">
                        <x-icons.app-icon name="trophy" class="size-3.5" />
                        {{ $application->scholarship?->scholarship_name }}
                    </span>
                </div>
            </div>
        </div>
    </header>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <x-ui.icon-badge icon="photo" tone="peach" />
                <div>
                    <p class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Pilih template') }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pilih template yang paling cocok dengan gayamu.') }}</p>
                </div>
            </div>

            @if ($templates->isEmpty())
                <x-ui.empty-state
                    icon="photo"
                    :title="__('Belum ada template')"
                    :description="__('Admin belum mengunggah template twibbon. Coba lagi nanti.')"
                />
            @else
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($templates as $template)
                        <button
                            type="button"
                            wire:click="selectTemplate({{ $template->id }})"
                            data-test="template-option"
                            @class([
                                'overflow-hidden rounded-2xl border-2 text-left transition',
                                'border-brand-teal ring-4 ring-brand-teal/20' => $template_id === $template->id,
                                'border-zinc-200 hover:border-brand-teal/50 dark:border-zinc-700' => $template_id !== $template->id,
                            ])
                        >
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($template->image_path) }}"
                                alt="{{ $template->name }}"
                                loading="lazy"
                                class="aspect-square w-full object-cover"
                            >
                            <span class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200">
                                @if ($template_id === $template->id)
                                    <x-icons.app-icon name="check-badge" class="size-3.5 text-brand-teal" />
                                @endif
                                {{ $template->name }}
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3 border-t border-brand-teal/10 pt-4 dark:border-white/10">
                <x-actions.button
                    type="button"
                    icon="sparkles"
                    wire:click="generate"
                    loading="document.twibbon"
                    data-test="generate-twibbon"
                >
                    {{ $generated_path ? __('Buat ulang twibbon') : __('Buat twibbon') }}
                </x-actions.button>

                <x-loading.inline loading="document.twibbon" target="generate" />
            </div>
        </x-ui.card>

        <x-ui.card class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <x-ui.icon-badge icon="eye" tone="teal" />
                <div>
                    <p class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Pratinjau') }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $generated_path ? __('Ini hasil twibbonmu. Unduh atau bagikan langsung.') : __('Hasil twibbonmu akan muncul di sini.') }}
                    </p>
                </div>
            </div>

            @if ($generated_path)
                <div class="sk-pop overflow-hidden rounded-2xl border border-brand-teal/20 shadow-soft dark:border-white/10">
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($generated_path) }}"
                        alt="{{ __('Twibbon :name', ['name' => $application->scholarship?->scholarship_name]) }}"
                        class="w-full"
                        data-test="twibbon-preview"
                    >
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($generated_path) }}"
                        download
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl brand-gradient px-4 text-sm font-bold text-brand-ink shadow-pop transition hover:brightness-105"
                        data-test="twibbon-download"
                    >
                        <x-icons.app-icon name="arrow-down-tray" class="size-4" />
                        {{ __('Unduh twibbon') }}
                    </a>
                </div>
            @else
                <div class="flex aspect-square w-full items-center justify-center rounded-2xl border border-dashed border-brand-teal/25 bg-warm-cream/70 dark:border-white/15 dark:bg-zinc-800/40">
                    <div class="flex flex-col items-center gap-2 text-zinc-400">
                        <x-ui.icon-badge icon="photo" tone="peach" solid />
                        <span class="text-sm font-medium">{{ __('Belum ada twibbon') }}</span>
                    </div>
                </div>
            @endif
        </x-ui.card>
    </div>
</div>

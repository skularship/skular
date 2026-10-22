@php
    $step_keys = array_keys($steps);
    $current_index = array_search($section, $step_keys, true);
    $current_index = $current_index === false ? 0 : (int) $current_index;
@endphp

<div
    class="mx-auto flex w-full max-w-4xl flex-col gap-6"
    data-learning-player
    data-request-error-message="{{ __('Koneksi bermasalah. Aksi tadi belum selesai — coba lagi ya.') }}"
>
    <header class="sk-rise hero-mesh relative overflow-hidden rounded-4xl border border-brand-teal/12 p-6 shadow-soft sm:p-7">
        <div class="pointer-events-none absolute -top-20 -right-12 size-52 rounded-full bg-brand-amber/25 blur-3xl" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-col gap-5">
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1.5 text-xs font-semibold text-zinc-600 shadow-sm backdrop-blur-sm transition hover:text-brand-teal dark:bg-zinc-900/60 dark:text-zinc-300 dark:hover:text-brand-teal-light"
                >
                    <x-icons.app-icon name="arrow-left" class="size-3.5" />
                    {{ __('Dashboard') }}
                </a>

                @if ($module->fase)
                    <x-ui.pill variant="brand" icon="flag">{{ $module->fase->title }}</x-ui.pill>
                @endif

                <x-ui.pill variant="info">{{ $module->type->label() }}</x-ui.pill>

                @if ($module->category === \App\Enums\ModuleCategory::Inti)
                    <x-ui.pill variant="success" icon="share">{{ __('Modul Inti · dipakai bersama') }}</x-ui.pill>
                @elseif ($module->category === \App\Enums\ModuleCategory::Template)
                    <x-ui.pill variant="warning" icon="academic-cap">
                        {{ __('Template :name', ['name' => $module->scholarship?->scholarship_name ?? __('beasiswa')]) }}
                    </x-ui.pill>
                @else
                    <x-ui.pill variant="neutral" icon="book-open">{{ __('Perpustakaan · opsional') }}</x-ui.pill>
                @endif

                @if ($module->duration)
                    <x-ui.pill icon="clock">{{ __(':minutes menit', ['minutes' => $module->duration]) }}</x-ui.pill>
                @endif

                @if ($module_completed)
                    <x-ui.pill variant="success" icon="check-badge">{{ __('Selesai') }}</x-ui.pill>
                @endif
            </div>

            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
                        {{ __('Modul :order — :title', ['order' => $module->modul_order, 'title' => $module->title]) }}
                    </h1>

                    @if (filled($module->goal))
                        <p class="mt-1.5 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ $module->goal }}</p>
                    @endif
                </div>

                <div class="shrink-0">
                    <x-ui.ring-progress :value="$this->progress" :size="88" :caption="__('Modul')" />
                </div>
            </div>

            <x-ui.progress :value="$this->progress" :label="__('Progress modul')" />
        </div>
    </header>

    <nav class="relative" aria-label="{{ __('Bagian modul') }}">
        <ol class="flex items-stretch gap-2 overflow-x-auto pb-1" data-module-steps>
            @foreach ($steps as $key => $label)
                @php
                    $is_active = $key === $section;
                    $is_done = $loop->index < $current_index;
                @endphp

                <li class="flex-1">
                    <button
                        type="button"
                        wire:click="goToSection('{{ $key }}')"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-70"
                        wire:target="goToSection('{{ $key }}')"
                        aria-current="{{ $is_active ? 'step' : 'false' }}"
                        @class([
                            'flex w-full min-w-[6.5rem] items-center justify-center gap-2 rounded-2xl border px-3 py-2.5 text-xs font-semibold transition',
                            'border-transparent brand-gradient text-brand-ink shadow-pop' => $is_active,
                            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/50 dark:text-emerald-300' => $is_done && ! $is_active,
                            'border-brand-teal/15 bg-white/70 text-zinc-500 hover:border-brand-teal/40 hover:text-brand-teal dark:border-white/10 dark:bg-zinc-800/60 dark:text-zinc-300' => ! $is_active && ! $is_done,
                        ])
                    >
                        <span @class([
                            'flex size-5 items-center justify-center rounded-full text-[0.625rem] font-bold',
                            'bg-white/50 text-brand-ink' => $is_active,
                            'bg-emerald-500 text-white' => $is_done && ! $is_active,
                            'bg-zinc-200/80 text-zinc-600 dark:bg-white/10 dark:text-zinc-300' => ! $is_active && ! $is_done,
                        ])>
                            @if ($is_done)
                                <x-icons.app-icon name="check" class="size-3" />
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </span>

                        {{ $label }}
                    </button>
                </li>
            @endforeach
        </ol>
    </nav>

    {{-- Shows up immediately while the next section is being prepared, without
         covering the page or adding an extra request. --}}
    <x-loading.inline
        loading="module.section"
        target="start,goToSection,nextSection,previousSection"
        :delay="false"
        class="px-1"
        data-test="module-section-loading"
    />

    <main class="flex flex-col gap-6">
        @if ($section === 'opening')
            @include('livewire.learning.partials.opening')
        @elseif ($section === 'pahami')
            @include('livewire.learning.partials.pahami')
        @elseif ($section === 'quick')
            @include('livewire.learning.partials.quick')
        @elseif ($section === 'praktik')
            @include('livewire.learning.partials.practice')
        @elseif ($section === 'checkpoint')
            @include('livewire.learning.partials.checkpoint')
        @endif
    </main>
</div>

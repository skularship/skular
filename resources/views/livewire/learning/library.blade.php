@php
    $tones = ['teal', 'amber', 'peach', 'mint'];
@endphp

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.section-heading
        :eyebrow="__('Perpustakaan')"
        eyebrow-icon="book-open"
        level="1"
        :title="__('Pelatihan Tambahan')"
        :description="__('Materi lintas beasiswa seperti TOEFL, Academic Writing, dan Public Speaking. Bisa diakses bebas kapan saja.')"
    />

    @if ($modules->isEmpty())
        <x-ui.empty-state
            icon="book-open"
            :title="__('Belum ada modul perpustakaan')"
            :description="__('Admin belum menambahkan pelatihan tambahan. Cek lagi nanti ya.')"
        />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modules as $entry)
                @php
                    $module = $entry['module'];
                    $checkpoint = $entry['checkpoint'];
                    $completed = $checkpoint?->isCompleted() ?? false;
                    $tone = $tones[$loop->index % count($tones)];
                @endphp

                <x-ui.card :variant="$completed ? 'mint' : 'plain'" hover class="flex h-full flex-col gap-4">
                    <div class="flex items-center justify-between gap-2">
                        <x-ui.icon-badge icon="book-open" :tone="$tone" solid />

                        @if ($completed)
                            <x-ui.pill variant="success" icon="check-badge">{{ __('Selesai') }}</x-ui.pill>
                        @endif
                    </div>

                    <div class="flex-1">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ $module->title }}</h3>
                        @if (filled($module->summary_material))
                            <p class="mt-1 line-clamp-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $module->summary_material }}</p>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-2 border-t border-brand-teal/10 pt-3 dark:border-white/10">
                        @if ($module->duration)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                <x-icons.app-icon name="clock" class="size-3.5" />
                                {{ __(':minutes menit', ['minutes' => $module->duration]) }}
                            </span>
                        @else
                            <span class="text-xs text-zinc-400">{{ __('Materi tambahan') }}</span>
                        @endif

                        <x-actions.link-button :href="route('modules.show', $module)" wire:navigate size="sm" variant="outline" iconTrailing="arrow-right">
                            {{ $completed ? __('Ulangi') : __('Mulai') }}
                        </x-actions.link-button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>

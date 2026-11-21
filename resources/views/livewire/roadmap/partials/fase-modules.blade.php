{{--
    Ordered module list of one visible fase (finished or active) on the roadmap page, drawn as a path.
    Data: $fase (App\Data\FaseProgressData), optional $compact (bool, default false).
    Never include this for a locked fase: locked fases only exist as FaseOutlineData and carry no modules.

    Layout (CSS in resources/css/app.css, prefix sk-path-):
    - md and up, not compact: winding path. Nodes zig-zag left/right of the centre, each card sits on
      the outer side next to its node, and one SVG cubic bezier per <li> joins a node to the next one.
    - below md, or compact (finished fases inside <details>): a vertical rail on the left, cards on the right.
    Status is only read from ModuleProgressData; unlock rules live in ProgressService.
--}}
@php
    $compact = (bool) ($compact ?? false);
    $path_modules = array_values($fase->modules);

    // The step the mentee should do now: the module in progress, else the first open unfinished one.
    $current_module = collect($path_modules)->first(fn ($module) => $module->isInProgress())
        ?? collect($path_modules)->first(fn ($module) => $module->isUnlocked() && ! $module->isCompleted());
    $current_module_id = $current_module?->id;
@endphp

<ol @class(['sk-path', 'sk-path--compact' => $compact]) data-test="roadmap-path">
    @foreach ($path_modules as $index => $module)
        @php
            $is_current = $module->id === $current_module_id;
            $next_module = $path_modules[$index + 1] ?? null;
            $side = $index % 2 === 0 ? 'left' : 'right';
            $segment_state = $module->isCompleted() ? 'done' : ($next_module?->isLocked() ? 'locked' : 'open');
            $score_label = $module->score !== null ? \App\Enums\AiScoreLabel::fromScore($module->score) : null;
        @endphp

        <li
            @class([
                'sk-path__item',
                'sk-path__item--'.$side,
                'sk-path__item--current' => $is_current,
            ])
            @if ($is_current) aria-current="step" @endif
            @if ($is_current && ! $compact)
                x-data
                x-init="$nextTick(() => { const box = $el.getBoundingClientRect(); if (box.top > window.innerHeight - 120) { $el.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); } })"
            @endif
            data-test="roadmap-module"
            wire:key="roadmap-module-{{ $module->id }}"
        >
            {{-- Connector to the next node: straight rail (mobile/compact) and a bezier curve (desktop). --}}
            @if ($next_module)
                <span class="sk-path__rail sk-path__rail--{{ $segment_state }}" aria-hidden="true"></span>

                @unless ($compact)
                    <svg class="sk-path__curve" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                        @if ($segment_state === 'done')
                            <defs>
                                <linearGradient id="sk-path-grad-{{ $fase->id }}-{{ $module->id }}" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" style="stop-color: var(--color-brand-amber)" />
                                    <stop offset="100%" style="stop-color: var(--color-brand-orange)" />
                                </linearGradient>
                            </defs>
                        @endif
                        <path
                            d="{{ $side === 'left' ? 'M0 0 C0 55 100 45 100 100' : 'M100 0 C100 55 0 45 0 100' }}"
                            class="sk-path__curve-line sk-path__curve-line--{{ $segment_state }}"
                            @if ($segment_state === 'done') style="stroke: url(#sk-path-grad-{{ $fase->id }}-{{ $module->id }})" @endif
                            vector-effect="non-scaling-stroke"
                        />
                    </svg>
                @endunless
            @endif

            {{-- Numbered node. Decorative: the card below carries the same information as text. --}}
            <span
                @class([
                    'sk-path__node',
                    'sk-path__node--done' => $module->isCompleted(),
                    'sk-path__node--current' => $is_current,
                    'sk-path__node--open' => ! $is_current && $module->isUnlocked() && ! $module->isCompleted(),
                    'sk-path__node--locked' => $module->isLocked(),
                ])
                aria-hidden="true"
                data-test="roadmap-path-node"
            >
                @if ($module->isCompleted())
                    <x-icons.app-icon name="check" class="size-5 stroke-[2.5]" />
                @else
                    {{ $module->modul_order }}
                @endif

                @if ($module->isLocked())
                    <span class="sk-path__node-lock">
                        <x-icons.app-icon name="lock-closed" class="size-3" />
                    </span>
                @endif
            </span>

            <div @class([
                'sk-path__card @container',
                'sk-path__card--current' => $is_current,
                'sk-path__card--open' => $module->isUnlocked() && ! $is_current,
                'sk-path__card--locked' => $module->isLocked(),
            ])>
                <div class="flex flex-col gap-3 @sm:flex-row @sm:items-center @sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <h3 @class([
                            'line-clamp-3 text-sm font-bold break-words sm:text-base',
                            'text-zinc-900' => $module->isUnlocked(),
                            'text-zinc-500' => $module->isLocked(),
                        ])>
                            {{ $module->title }}
                        </h3>

                        <p class="sr-only">{{ __('Status: :status', ['status' => $module->status->label()]) }}</p>

                        <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1.5 text-xs text-zinc-500">
                            <span class="font-semibold">{{ __('Modul :order', ['order' => $module->modul_order]) }}</span>
                            <span aria-hidden="true">·</span>
                            <x-ui.pill :variant="$module->isLocked() ? 'neutral' : 'info'" class="py-0.5">{{ $module->category->label() }}</x-ui.pill>

                            @if ($module->duration)
                                <span class="inline-flex items-center gap-1">
                                    <x-icons.app-icon name="clock" class="size-3.5" />
                                    {{ __(':minutes menit', ['minutes' => $module->duration]) }}
                                </span>
                            @endif

                            @if ($score_label)
                                <x-ui.pill :variant="$score_label->variant()" icon="star" class="py-0.5">{{ $score_label->label() }}</x-ui.pill>
                            @endif
                        </div>

                        @if ($module->isLocked())
                            <p class="mt-2 text-xs font-medium text-zinc-500">{{ __('Selesaikan modul sebelumnya') }}</p>
                        @endif
                    </div>

                    <x-roadmap.path-button :module="$module" class="self-start @sm:self-center" />
                </div>
            </div>
        </li>
    @endforeach
</ol>

{{--
    Ordered module list of one visible fase (finished or active) on the roadmap page.
    Data: $fase (App\Data\FaseProgressData). Never include this for a locked fase:
    locked fases only exist as FaseOutlineData and carry no modules.
--}}
<ol class="flex flex-col">
    @foreach ($fase->modules as $module)
        <li class="relative flex gap-4 pb-6 last:pb-0" data-test="roadmap-module">
            @if (! $loop->last)
                <span @class([
                    'absolute top-10 bottom-0 left-[1.125rem] w-0.5 rounded-full',
                    'brand-gradient' => $module->isCompleted(),
                    'bg-zinc-200' => ! $module->isCompleted(),
                ]) aria-hidden="true"></span>
            @endif

            <span @class([
                'relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full border-2 shadow-sm',
                'brand-gradient border-transparent text-brand-ink' => $module->isCompleted(),
                'border-brand-teal bg-white text-brand-teal ring-4 ring-brand-teal/15' => $module->isInProgress(),
                'border-brand-teal/60 bg-white text-brand-teal' => ! $module->isCompleted() && ! $module->isInProgress() && $module->isUnlocked(),
                'border-zinc-200 bg-zinc-50 text-zinc-400' => $module->isLocked(),
            ]) aria-hidden="true">
                <x-icons.app-icon :name="$module->statusIcon()" class="size-4" />
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p @class([
                        'text-sm font-semibold break-words',
                        'text-zinc-900' => $module->isUnlocked(),
                        'text-zinc-400' => $module->isLocked(),
                    ])>
                        @if ($module->isUnlocked())
                            <a href="{{ route('modules.show', $module->id) }}" wire:navigate class="transition hover:text-brand-teal">
                                {{ $module->modul_order }}. {{ $module->title }}
                            </a>
                        @else
                            {{ $module->modul_order }}. {{ $module->title }}
                        @endif
                    </p>

                    <x-ui.pill variant="info">{{ $module->category->label() }}</x-ui.pill>

                    @if ($module->duration)
                        <span class="inline-flex items-center gap-1 text-xs text-zinc-500">
                            <x-icons.app-icon name="clock" class="size-3.5" />
                            {{ __(':minutes menit', ['minutes' => $module->duration]) }}
                        </span>
                    @endif
                </div>

                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                    <x-ui.pill :variant="$module->isCompleted() ? 'success' : ($module->isInProgress() ? 'info' : 'neutral')" :icon="$module->statusIcon()">{{ $module->status->label() }}</x-ui.pill>

                    @if ($module->score !== null)
                        @php $module_score_label = \App\Enums\AiScoreLabel::fromScore($module->score); @endphp
                        <x-ui.pill :variant="$module_score_label->variant()" icon="star">{{ $module_score_label->label() }}</x-ui.pill>
                    @endif

                    @if ($module->isLocked())
                        <span class="inline-flex items-center gap-1 text-xs text-zinc-500">
                            <x-icons.app-icon name="lock-closed" class="size-3.5" />
                            {{ __('Selesaikan modul sebelumnya untuk membuka') }}
                        </span>
                    @else
                        <a href="{{ route('modules.show', $module->id) }}" wire:navigate class="inline-flex min-h-8 items-center gap-1 text-xs font-bold text-brand-teal transition hover:underline">
                            {{ $module->isCompleted() ? __('Ulangi modul') : ($module->isInProgress() ? __('Lanjutkan modul') : __('Mulai modul')) }}
                            <x-icons.app-icon name="arrow-right" class="size-3" />
                        </a>
                    @endif
                </div>
            </div>
        </li>
    @endforeach
</ol>

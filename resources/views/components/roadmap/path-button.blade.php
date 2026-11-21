{{--
    Raised ("3D") action button of one module on the roadmap path (<x-roadmap.path-button>).
    Props: module (App\Data\ModuleProgressData). Open modules get a real link to modules.show
    (Mulai / Lanjutkan / Ulangi); locked modules get a disabled, non-link "Terkunci" chip.
--}}
@props([
    'module',
])

@if ($module->isLocked())
    <span {{ $attributes->class(['sk-path-btn sk-path-btn--locked']) }} aria-disabled="true" data-test="roadmap-module-action">
        <x-icons.app-icon name="lock-closed" class="size-4" />
        {{ __('Terkunci') }}
    </span>
@else
    <a
        href="{{ route('modules.show', $module->id) }}"
        wire:navigate
        {{ $attributes->class([
            'sk-path-btn',
            'sk-path-btn--done' => $module->isCompleted(),
            'sk-path-btn--primary' => ! $module->isCompleted(),
        ]) }}
        data-test="roadmap-module-action"
    >
        @if ($module->isCompleted())
            <x-icons.app-icon name="arrow-path" class="size-4" />
            {{ __('Ulangi') }}
        @else
            {{ $module->isInProgress() ? __('Lanjutkan') : __('Mulai') }}
            <x-icons.app-icon name="arrow-right" class="size-4" />
        @endif
        <span class="sr-only">{{ $module->title }}</span>
    </a>
@endif

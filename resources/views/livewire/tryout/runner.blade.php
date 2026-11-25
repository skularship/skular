{{-- Try Out runner shell (App\Livewire\Tryout\Runner). Transition screen between subtests (the next timer starts on
     "Lanjut"), otherwise the child of the active subtest, keyed by its row so a new subtest mounts fresh.
     Data: attempt, current (TryoutAttemptSection|null), previous, previous_answered, show_transition. --}}
<div class="min-h-screen">
    @if ($current === null)
        <div class="flex min-h-[70vh] items-center justify-center p-6">
            <div class="flex flex-col items-center gap-3 text-center">
                <x-icons.app-icon name="spinner" class="size-8 animate-spin text-brand-teal" />
                <p class="text-sm font-semibold text-zinc-600">{{ __('Menyiapkan hasilmu...') }}</p>
            </div>
        </div>
    @elseif ($show_transition)
        @php
            $next_section = $current->section;
            $is_first = $previous === null;
        @endphp

        <header class="bg-brand-teal-deep text-white">
            <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-4 sm:px-6">
                <span class="text-xl font-black tracking-tight">skular</span>
                <span class="text-sm font-bold text-white/80">{{ $attempt->tryout->title }}</span>
            </div>
        </header>

        <div class="mx-auto flex max-w-xl flex-col items-center gap-6 px-4 py-12 text-center sm:py-20" data-test="tryout-transition">
            @unless ($is_first)
                <span class="flex size-16 items-center justify-center rounded-3xl bg-brand-mint-soft text-emerald-600 shadow-soft">
                    <x-icons.app-icon name="check-circle" variant="s" class="size-9" />
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold text-zinc-900 sm:text-3xl">{{ __(':skill selesai', ['skill' => $previous->skill->getLabel()]) }}</h1>
                    <p class="mt-2 text-base text-zinc-600">
                        @if ($previous->skill->isObjective())
                            {{ __(':count soal terjawab. Jawabanmu sudah tersimpan.', ['count' => $previous_answered]) }}
                        @else
                            {{ __('Jawabanmu sudah tersimpan dan akan dinilai AI.') }}
                        @endif
                    </p>
                </div>
            @endunless

            <div class="w-full rounded-4xl border border-brand-teal/10 bg-white p-6 text-start shadow-lift">
                <p class="text-xs font-extrabold tracking-wide text-brand-teal uppercase">{{ $is_first ? __('Subtes pertama') : __('Berikutnya') }}</p>
                <div class="mt-3 flex items-center gap-4">
                    <span class="flex size-14 items-center justify-center rounded-2xl bg-brand-teal-soft text-brand-teal">
                        <x-icons.app-icon :name="$current->skill->icon()" class="size-7" />
                    </span>
                    <div>
                        <p class="text-xl font-extrabold text-zinc-900">{{ $current->skill->getLabel() }}</p>
                        <p class="text-sm text-zinc-600">{{ $next_section->summaryLabel() }} · {{ $next_section->durationLabel() }}</p>
                    </div>
                </div>
                @if (filled($next_section->instructions))
                    <p class="mt-4 text-sm text-zinc-600">{{ $next_section->instructions }}</p>
                @endif
                <p class="mt-4 rounded-2xl bg-brand-cream px-4 py-3 text-sm text-zinc-600">
                    {{ __('Waktu baru mulai berjalan setelah kamu menekan Lanjut. Ambil napas dulu ya.') }}
                </p>
            </div>

            <button
                type="button"
                wire:click="startNext"
                wire:loading.attr="disabled"
                class="flex h-14 w-full max-w-xs items-center justify-center gap-2 rounded-2xl bg-brand-teal text-base font-extrabold text-white shadow-soft transition hover:bg-brand-teal-deep"
                data-test="start-next-section"
            >
                <span wire:loading.remove wire:target="startNext">{{ __('Lanjut ke :skill', ['skill' => $current->skill->getLabel()]) }}</span>
                <span wire:loading wire:target="startNext">{{ __('Menyiapkan...') }}</span>
            </button>
        </div>
    @else
        @switch($current->skill)
            @case(\App\Enums\TryoutSkill::Writing)
                <livewire:tryout.sections.writing-section :attempt_section_id="$current->id" :key="'section-'.$current->id" />
                @break
            @case(\App\Enums\TryoutSkill::Speaking)
                <livewire:tryout.sections.speaking-section :attempt_section_id="$current->id" :key="'section-'.$current->id" />
                @break
            @default
                <livewire:tryout.sections.objective-section :attempt_section_id="$current->id" :key="'section-'.$current->id" />
        @endswitch
    @endif
</div>

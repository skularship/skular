{{-- Event list (App\Livewire\Events\Index): tabs "Akan datang" / "Rekaman", one large card per event (poster, title, WIB time, one main button). Data: events, show_recordings, registered_ids. --}}
<div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-ui.section-heading
        :eyebrow="__('Webinar, kelas, mentoring')"
        eyebrow-icon="video-camera"
        level="1"
        :title="__('Event Skular')"
        :description="__('Ikut acara langsung bersama tim Skular. Daftar di sini, link acaranya muncul H-1.')"
    />

    <div class="flex gap-2" role="tablist" aria-label="{{ __('Jenis daftar acara') }}" data-tour="event-tabs">
        @foreach (['akan-datang' => __('Akan datang'), 'rekaman' => __('Rekaman')] as $key => $label)
            <button
                type="button"
                role="tab"
                aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                wire:click="$set('tab', '{{ $key }}')"
                @class([
                    'min-h-12 rounded-full px-6 text-base font-bold transition',
                    'brand-gradient text-brand-ink shadow-pop' => $tab === $key,
                    'border-2 border-zinc-300 bg-white text-zinc-700 hover:border-brand-teal/50 dark:border-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-200' => $tab !== $key,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($events->isEmpty())
        <x-ui.empty-state
            icon="calendar-days"
            :title="$show_recordings ? __('Belum ada rekaman') : __('Belum ada acara terdekat')"
            :description="$show_recordings
                ? __('Rekaman acara yang sudah lewat akan muncul di sini.')
                : __('Acara baru akan muncul di sini. Cek lagi nanti ya.')"
            data-test="events-empty"
        />
    @else
        <div class="grid gap-5 sm:grid-cols-2" data-test="event-list">
            @foreach ($events as $event)
                @php
                    $is_registered = in_array($event->id, $registered_ids, true);
                    $is_full = $event->capacity !== null && ($event->seated_registrations_count ?? 0) >= $event->capacity;
                @endphp

                <x-ui.card padding="none" class="flex flex-col overflow-hidden" wire:key="event-{{ $event->id }}" data-test="event-card">
                    <div class="aspect-video w-full overflow-hidden bg-brand-teal/10">
                        @if ($event->image_url)
                            <img src="{{ $event->image_url }}" alt="" class="size-full object-cover" loading="lazy">
                        @else
                            <div class="flex size-full items-center justify-center">
                                <x-icons.app-icon :name="$event->type->icon()" class="size-14 text-brand-teal/60" />
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col gap-3 p-6">
                        <div class="flex flex-wrap gap-2">
                            <x-ui.pill variant="info" :icon="$event->type->icon()">{{ $event->type->label() }}</x-ui.pill>
                            @if ($is_registered)
                                <x-ui.pill variant="success" icon="check-badge">{{ __('Terdaftar') }}</x-ui.pill>
                            @endif
                        </div>

                        <h2 class="text-xl font-extrabold text-zinc-900 dark:text-white">{{ $event->title }}</h2>

                        <p class="flex items-start gap-2 text-base text-zinc-700 dark:text-zinc-300">
                            <x-icons.app-icon name="calendar-days" class="mt-0.5 size-5 shrink-0 text-brand-teal" />
                            {{ $event->startsAtLabel() }}
                        </p>

                        <div class="mt-auto pt-2">
                            @if ($show_recordings)
                                <x-actions.link-button :href="route('events.show', $event)" wire:navigate size="lg" variant="outline" icon="play" class="w-full">
                                    {{ __('Tonton rekaman') }}
                                </x-actions.link-button>
                            @elseif ($is_registered)
                                <x-actions.link-button :href="route('events.show', $event)" wire:navigate size="lg" variant="outline" class="w-full">
                                    {{ __('Lihat detail') }}
                                </x-actions.link-button>
                            @elseif ($is_full)
                                <x-actions.link-button :href="route('events.show', $event)" wire:navigate size="lg" variant="outline" class="w-full">
                                    {{ __('Penuh') }}
                                </x-actions.link-button>
                            @else
                                <x-actions.link-button :href="route('events.show', $event)" wire:navigate size="lg" iconTrailing="arrow-right" class="w-full">
                                    {{ __('Daftar') }}
                                </x-actions.link-button>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    {{-- Onboarding coach marks for this page (opens once; replay from /panduan). --}}
    <livewire:onboarding.product-tour tour="events" />
</div>

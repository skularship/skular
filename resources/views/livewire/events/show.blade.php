{{-- Event detail (App\Livewire\Events\Show): poster, WIB time, Markdown description, one main action ("Daftar sekarang" → short questions → done), the meeting link from H-1 for registered mentees only, "Tambah ke kalender" (.ics), and the 1-5 feedback card after the event. --}}
@php
    $has_ended = $event->hasEnded();
    $has_started = $event->hasStarted();
    $input_classes = 'block w-full rounded-2xl border-2 border-zinc-300 bg-white px-4 py-3 text-base text-zinc-800 focus:border-brand-teal focus:outline-hidden focus:ring-2 focus:ring-brand-teal/30 dark:border-zinc-600 dark:bg-white/10 dark:text-zinc-200';
@endphp

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <a href="{{ route('events.index') }}" wire:navigate class="inline-flex min-h-11 w-fit items-center gap-2 text-base font-semibold text-brand-teal hover:underline">
        <x-icons.app-icon name="arrow-left" class="size-5" />
        {{ __('Semua event') }}
    </a>

    <article class="overflow-hidden rounded-4xl border border-brand-teal/15 bg-white shadow-soft dark:bg-zinc-800/60">
        @if ($event->image_url)
            <img src="{{ $event->image_url }}" alt="" class="aspect-video w-full object-cover">
        @endif

        <div class="flex flex-col gap-5 p-6 sm:p-8">
            <div class="flex flex-wrap gap-2">
                <x-ui.pill variant="info" :icon="$event->type->icon()">{{ $event->type->label() }}</x-ui.pill>
                @if ($is_registered)
                    <x-ui.pill variant="success" icon="check-badge">{{ __('Terdaftar') }}</x-ui.pill>
                @endif
            </div>

            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">{{ $event->title }}</h1>

            <p class="flex items-start gap-2 text-lg font-semibold text-zinc-800 dark:text-zinc-200" data-test="event-time">
                <x-icons.app-icon name="calendar-days" class="mt-1 size-5 shrink-0 text-brand-teal" />
                {{ $event->startsAtLabel() }}
            </p>

            {{-- Main action --}}
            <div class="rounded-3xl bg-brand-teal/6 p-5 dark:bg-white/5" data-test="event-action">
                @if ($has_ended)
                    @if ($event->recording_url)
                        <x-actions.link-button :href="$event->recording_url" target="_blank" rel="noopener" size="lg" icon="play" class="w-full sm:w-auto">
                            {{ __('Tonton rekaman') }}
                        </x-actions.link-button>
                    @else
                        <p class="text-base text-zinc-700 dark:text-zinc-300">{{ __('Acara ini sudah selesai.') }}</p>
                    @endif
                @elseif ($is_registered)
                    <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ __('Kamu terdaftar.') }}</p>

                    @if ($can_see_link)
                        <x-actions.link-button :href="$event->location_url" target="_blank" rel="noopener" size="lg" icon="video-camera" class="mt-4 w-full sm:w-auto" data-test="event-link">
                            {{ __('Masuk ke acara') }}
                        </x-actions.link-button>
                    @else
                        <p class="mt-1 text-base text-zinc-700 dark:text-zinc-300">{{ __('Link acara muncul di sini H-1.') }}</p>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-actions.link-button :href="route('events.calendar', $event)" size="lg" variant="outline" icon="calendar-days" data-test="add-to-calendar">
                            {{ __('Tambah ke kalender') }}
                        </x-actions.link-button>

                        @unless ($has_started)
                            <x-actions.button type="button" variant="ghost" wire:click="cancel" wire:confirm="{{ __('Batalkan pendaftaranmu di acara ini?') }}" class="min-h-11">
                                {{ __('Batalkan pendaftaran') }}
                            </x-actions.button>
                        @endunless
                    </div>
                @elseif ($has_started)
                    <p class="text-base text-zinc-700 dark:text-zinc-300">{{ __('Pendaftaran acara ini sudah ditutup.') }}</p>
                @elseif ($is_full)
                    <x-actions.button type="button" size="lg" disabled class="w-full sm:w-auto" data-test="event-full">
                        {{ __('Penuh') }}
                    </x-actions.button>
                @elseif ($answering)
                    <form wire:submit="register" class="flex flex-col gap-5" data-test="registration-questions">
                        <p class="text-base font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Jawab pertanyaan singkat ini dulu ya.') }}</p>

                        @foreach ($questions as $index => $question)
                            <div class="flex flex-col gap-2">
                                <label for="event-answer-{{ $index }}" class="text-base font-bold text-zinc-900 dark:text-white">{{ $question }}</label>
                                <textarea id="event-answer-{{ $index }}" wire:model="answers.{{ $index }}" rows="2" class="{{ $input_classes }}"></textarea>
                                @error('answers.'.$index)
                                    <p class="text-base font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach

                        <x-actions.button type="submit" size="lg" icon="check" class="w-full sm:w-auto" data-test="confirm-registration">
                            {{ __('Kirim dan daftar') }}
                        </x-actions.button>
                    </form>
                @else
                    <x-actions.button type="button" size="lg" iconTrailing="arrow-right" wire:click="startRegistration" class="w-full sm:w-auto" data-test="register-event">
                        {{ __('Daftar sekarang') }}
                    </x-actions.button>
                @endif

                @error('event')
                    <p class="mt-3 text-base font-medium text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            @if (filled($event->description))
                <x-learning.rich-content :content="$event->description" />
            @endif
        </div>
    </article>

    @if ($has_ended && $is_registered)
        <x-ui.card variant="teal" class="flex flex-col gap-5" data-test="event-feedback">
            <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Seberapa bermanfaat acara ini untukmu?') }}</p>

            <div class="grid grid-cols-5 gap-2 sm:gap-3">
                @foreach (range(1, 5) as $score)
                    <label class="flex min-h-14 cursor-pointer items-center justify-center rounded-2xl border-2 border-zinc-300 bg-white text-xl font-extrabold text-zinc-700 transition has-[:checked]:border-brand-teal has-[:checked]:bg-brand-teal has-[:checked]:text-white dark:border-zinc-600 dark:bg-zinc-900/50 dark:text-zinc-200">
                        <input type="radio" wire:model="feedback_score" value="{{ $score }}" class="sr-only">
                        {{ $score }}
                    </label>
                @endforeach
            </div>
            <div class="flex justify-between text-sm font-medium text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Tidak bermanfaat') }}</span>
                <span>{{ __('Sangat bermanfaat') }}</span>
            </div>
            @error('event_feedback_score')
                <p class="text-base font-medium text-red-700" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex flex-col gap-2">
                <label for="event-feedback-text" class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Satu kalimat masukan untuk kami') }}</label>
                <textarea id="event-feedback-text" wire:model="feedback_text" rows="2" class="{{ $input_classes }}"></textarea>
                @error('event_feedback_text')
                    <p class="text-base font-medium text-red-700" role="alert">{{ $message }}</p>
                @enderror
                @error('event_feedback')
                    <p class="text-base font-medium text-red-700" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <x-actions.button type="button" size="lg" icon="paper-airplane" wire:click="sendFeedback" class="w-full sm:w-auto">
                {{ __('Kirim masukan') }}
            </x-actions.button>
        </x-ui.card>
    @endif
</div>

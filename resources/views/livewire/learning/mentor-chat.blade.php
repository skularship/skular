{{-- Rendered in the practice flow, not as a fixed overlay. The practice section
     animates with a transform, and a fixed panel would either be trapped by
     that containing block or cover the form actions while scrolling. --}}
<div class="w-full" data-test="mentor-chat">
    @if ($open)
        <section
            class="sk-pop flex max-h-[70vh] w-full flex-col overflow-hidden rounded-3xl border border-brand-teal/15 bg-white shadow-lift dark:border-white/10 dark:bg-zinc-900"
            aria-label="{{ __('AI Mentor') }}"
        >
            <header class="flex items-center gap-3 bg-linear-to-br from-brand-teal to-brand-teal-deep px-4 py-3.5 text-white">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-white backdrop-blur-sm">
                    <x-icons.app-icon name="sparkles" class="size-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold">{{ __('AI Mentor') }}</p>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-white/80">
                        <span class="inline-block size-2 rounded-full bg-emerald-300"></span>
                        {{ __('Siap membantu belajar') }}
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="toggle"
                    class="rounded-xl p-1.5 text-white/80 transition hover:bg-white/15 hover:text-white"
                    aria-label="{{ __('Tutup AI Mentor') }}"
                >
                    <x-icons.app-icon name="x-mark" class="size-5" />
                </button>
            </header>

            <div class="flex-1 space-y-3 overflow-y-auto bg-warm-cream/70 px-4 py-4 dark:bg-zinc-950/40" data-test="mentor-transcript">
                <div class="flex items-start gap-2">
                    <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full brand-gradient text-brand-ink">
                        <x-icons.app-icon name="sparkles" class="size-4" />
                    </span>
                    <p class="rounded-2xl rounded-tl-sm border border-brand-teal/10 bg-white px-3.5 py-2.5 text-sm text-zinc-700 shadow-sm dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-200">
                        {{ __('Hai! Aku AI Mentor untuk modul ini. Ada materi atau bagian praktik yang ingin kamu diskusikan?') }}
                    </p>
                </div>

                @if ($messages === [])
                    <div class="flex flex-col gap-2 pt-2" data-test="mentor-quick-prompts">
                        <p class="text-xs font-bold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Contoh pertanyaan') }}</p>
                        @foreach ($quick_prompts as $prompt)
                            <button
                                type="button"
                                wire:click="useQuickPrompt({{ \Illuminate\Support\Js::from($prompt) }})"
                                wire:loading.attr="disabled"
                                wire:target="useQuickPrompt"
                                class="flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-3.5 py-2.5 text-left text-sm text-zinc-600 transition hover:border-brand-teal hover:text-brand-teal disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                            >
                                <x-icons.app-icon name="chat-bubble-left" class="size-4 shrink-0 text-brand-teal dark:text-brand-teal-light" />
                                {{ $prompt }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @foreach ($messages as $message)
                    @if ($message['role'] === 'user')
                        <div class="flex justify-end">
                            <p class="max-w-[85%] rounded-2xl rounded-tr-sm bg-brand-teal px-3.5 py-2.5 text-sm whitespace-pre-line text-white shadow-sm">
                                {{ $message['content'] }}
                            </p>
                        </div>
                    @else
                        <div class="flex items-start gap-2">
                            <span @class([
                                'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full',
                                'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-300' => $message['error'] ?? false,
                                'brand-gradient text-brand-ink' => ! ($message['error'] ?? false),
                            ])>
                                <x-icons.app-icon :name="($message['error'] ?? false) ? 'exclamation-triangle' : 'sparkles'" class="size-4" />
                            </span>
                            <p @class([
                                'max-w-[85%] rounded-2xl rounded-tl-sm px-3.5 py-2.5 text-sm whitespace-pre-line shadow-sm',
                                'border border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-950/40 dark:text-red-200' => $message['error'] ?? false,
                                'border border-brand-teal/10 bg-white text-zinc-700 dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-200' => ! ($message['error'] ?? false),
                            ])>
                                {{ $message['content'] }}
                            </p>
                        </div>
                    @endif
                @endforeach

                <x-loading.ai loading="ai.mentor" target="send,retry,useQuickPrompt" skeleton :skeleton-lines="2" data-test="mentor-loading" />

                @if ($failed_question !== null)
                    <button
                        type="button"
                        wire:click="retry"
                        wire:loading.attr="disabled"
                        wire:target="retry"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs font-bold text-zinc-600 transition hover:border-brand-teal hover:text-brand-teal disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                        data-test="mentor-retry"
                    >
                        <x-icons.app-icon name="arrow-path" class="size-3.5" />
                        {{ __('Coba lagi') }}
                    </button>
                @endif
            </div>

            <form wire:submit.prevent="send" class="border-t border-brand-teal/10 bg-white px-4 py-3 dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-end gap-2">
                    <label class="sr-only" for="mentor-question">{{ __('Pertanyaan untuk AI Mentor') }}</label>
                    <textarea
                        id="mentor-question"
                        wire:model="question"
                        rows="1"
                        maxlength="{{ \App\Services\ModuleMentorService::MAX_QUESTION_LENGTH }}"
                        placeholder="{{ __('Tulis pertanyaanmu...') }}"
                        class="block max-h-28 min-h-10 w-full resize-none rounded-2xl border border-zinc-200 bg-white px-3.5 py-2.5 text-base text-zinc-700 transition focus:outline-hidden focus:ring-2 focus:ring-brand-teal sm:text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                    ></textarea>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="send"
                        class="flex size-10 shrink-0 items-center justify-center rounded-2xl border-transparent brand-gradient text-brand-ink shadow-pop transition hover:brightness-105 disabled:opacity-50"
                        aria-label="{{ __('Kirim pertanyaan') }}"
                        data-test="mentor-send"
                    >
                        <x-icons.app-icon name="paper-airplane" class="size-4" wire:loading.remove wire:target="send" />
                        <x-icons.spinner class="hidden size-4" wire:loading.class.remove="hidden" wire:target="send" />
                    </button>
                </div>

                @error('question')
                    <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400" data-test="mentor-error">{{ $message }}</p>
                @enderror

                <p class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] text-zinc-400 dark:text-zinc-500">
                    <x-icons.app-icon name="lock-closed" class="size-3" />
                    {{ __('AI Mentor membantu berpikir, bukan mengisi jawabanmu.') }}
                </p>
            </form>
        </section>
    @else
        <button
            type="button"
            wire:click="toggle"
            class="flex w-full items-center gap-3 rounded-3xl border border-brand-teal/20 bg-white/80 p-4 text-left shadow-soft transition hover:border-brand-teal/50 hover:bg-white dark:border-white/10 dark:bg-zinc-900/60 dark:hover:border-brand-teal-light/40"
            data-test="mentor-launcher"
        >
            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl brand-gradient-teal text-white shadow-soft">
                <x-icons.app-icon name="chat-bubble-left-right" class="size-5" />
            </span>

            <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold text-zinc-900 dark:text-white">{{ __('AI Mentor') }}</span>
                <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Tanya materi atau bagian latihan yang masih membingungkan.') }}
                </span>
            </span>

            <x-icons.app-icon name="chevron-down" class="size-5 shrink-0 text-brand-teal dark:text-brand-teal-light" />
        </button>
    @endif
</div>

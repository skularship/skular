{{-- Tes IQ mentor chat (App\Livewire\Iq\MentorChat, paid access). Shows the anchored question, the transcript, the
     questions left today and the question box. Data: messages, question, question_position, remaining (computed), limit. --}}
<div class="flex max-h-[80vh] flex-col overflow-hidden rounded-4xl border border-brand-teal/10 bg-white shadow-lift" data-test="iq-mentor-chat">
    <div class="flex items-center gap-3 bg-brand-teal-deep px-5 py-4 text-white">
        <span class="flex size-10 items-center justify-center rounded-2xl bg-white/15"><x-icons.app-icon name="sparkles" class="size-5 text-brand-amber" /></span>
        <div>
            <p class="font-extrabold">{{ __('Mentor AI Tes IQ') }}</p>
            <p class="text-xs text-white/75">{{ __('Sisa :n dari :limit pertanyaan hari ini', ['n' => $this->remaining, 'limit' => $limit]) }}</p>
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-3 overflow-y-auto p-4">
        @if ($messages === [])
            <p class="rounded-2xl bg-brand-cream px-4 py-3 text-sm text-zinc-600">{{ __('Pilih "Tanya mentor soal ini" di salah satu soal, atau tanyakan strategi mengerjakan subtes tertentu.') }}</p>
        @endif

        @foreach ($messages as $index => $message)
            <div wire:key="iq-msg-{{ $index }}" @class([
                'max-w-[90%] rounded-2xl px-4 py-2.5 text-sm whitespace-pre-line',
                'self-end bg-brand-teal text-white' => $message['role'] === 'user',
                'self-start bg-zinc-100 text-zinc-800' => $message['role'] === 'assistant' && ! ($message['error'] ?? false),
                'self-start bg-brand-rose-soft text-red-800' => $message['error'] ?? false,
            ])>{{ $message['content'] }}</div>
        @endforeach

        <div wire:loading wire:target="send" class="self-start rounded-2xl bg-zinc-100 px-4 py-2.5 text-sm text-zinc-500">{{ __('Mentor sedang menulis...') }}</div>
    </div>

    <form wire:submit="send" class="flex flex-col gap-2 border-t border-zinc-100 p-4">
        @if ($question_position)
            <span class="flex w-fit items-center gap-2 rounded-full bg-brand-teal-soft px-3 py-1 text-xs font-bold text-brand-teal-deep">
                {{ __('Soal :n', ['n' => $question_position]) }}
                <button type="button" wire:click="clearFocus" aria-label="{{ __('Hapus') }}"><x-icons.app-icon name="x-mark" class="size-3.5" /></button>
            </span>
        @endif
        <textarea wire:model="question" rows="2" maxlength="1000" class="w-full resize-none rounded-2xl border-2 border-zinc-200 p-3 text-sm focus:border-brand-teal focus:ring-brand-teal/20" placeholder="{{ __('Tulis pertanyaanmu...') }}" data-test="iq-mentor-question"></textarea>
        @error('question')
            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" wire:loading.attr="disabled" @disabled($this->remaining <= 0) class="h-11 rounded-2xl bg-brand-teal font-bold text-white hover:bg-brand-teal-deep disabled:opacity-50">{{ __('Kirim') }}</button>
    </form>
</div>

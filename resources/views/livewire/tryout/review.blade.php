{{-- Try Out review (App\Livewire\Tryout\Review). Pro: filters (subtest, wrong only), one card per answered question
     with "Jawabanmu" (rose/mint) vs "Kunci", the explanation and "Tanya mentor soal ini", plus the mentor chat.
     Gratis: locked card with the Pro CTA; no key or explanation is rendered. Data: attempt, locked, items, total, wrong. --}}
<div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <a href="{{ route('tryout.result', $attempt) }}" wire:navigate class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-zinc-500 hover:text-brand-teal">
        <x-icons.app-icon name="arrow-left" class="size-4" />
        {{ __('Kembali ke hasil') }}
    </a>

    <x-ui.section-heading :eyebrow="__('Pembahasan')" eyebrow-icon="light-bulb" level="1" :title="$attempt->tryout->title" :description="__('Lihat kunci, pembahasan, dan tanya mentor AI untuk soal yang masih bikin bingung.')" />

    @if ($locked)
        <x-ui.card variant="peach" padding="lg" class="flex flex-col items-center gap-4 text-center" data-test="review-locked">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-white text-brand-orange shadow-soft">
                <x-icons.app-icon name="lock-closed" class="size-7" />
            </span>
            <h2 class="text-xl font-extrabold text-zinc-900">{{ __('Pembahasan tersedia untuk paket Pro') }}</h2>
            <p class="max-w-lg text-sm text-zinc-600">{{ __('Dengan Pro kamu bisa melihat kunci jawaban, pembahasan tiap soal, teori IELTS, bertanya ke mentor AI, dan mengulang Try Out.') }}</p>
            @if (config('pricing.enabled'))
                <x-actions.link-button :href="route('premium')" variant="primary" size="lg" icon="star">{{ __('Lihat paket Pro') }}</x-actions.link-button>
            @endif
        </x-ui.card>
    @else
        <div class="grid gap-6 lg:grid-cols-[1fr_24rem]">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="$toggle('wrong_only')" @class(['h-10 rounded-full px-4 text-sm font-bold', 'bg-brand-teal text-white' => $wrong_only, 'bg-zinc-100 text-zinc-700' => ! $wrong_only]) data-test="filter-wrong">
                        {{ __('Salah saja') }} ({{ $wrong }})
                    </button>
                    @foreach (['' => __('Semua'), 'listening' => 'Listening', 'reading' => 'Reading'] as $value => $label)
                        <button type="button" wire:click="$set('skill', '{{ $value }}')" @class(['h-10 rounded-full px-4 text-sm font-bold', 'bg-brand-teal-soft text-brand-teal-deep ring-2 ring-brand-teal/30' => $skill === $value, 'bg-zinc-100 text-zinc-700' => $skill !== $value])>
                            {{ $label }}
                        </button>
                    @endforeach
                    <span class="ms-auto text-sm text-zinc-500">{{ __(':n soal dijawab', ['n' => $total]) }}</span>
                </div>

                @forelse ($items as $item)
                    @php $label = ucfirst($item->skill).' · '.__('Soal').' '.$item->sequence; @endphp
                    <article class="rounded-3xl border border-brand-teal/10 bg-white p-5 shadow-soft sm:p-6" wire:key="review-{{ $item->code }}" data-test="review-item">
                        <p class="text-xs font-extrabold text-brand-teal">{{ $label }} · {{ __('level :n', ['n' => $item->level_at_answer]) }}@if ($item->unit_title) · {{ $item->unit_title }}@endif</p>
                        <h3 class="mt-2 text-base font-extrabold text-zinc-900">{{ $item->prompt }}</h3>

                        @if ($item->options !== [])
                            <ul class="mt-3 grid gap-1.5 text-sm text-zinc-700">
                                @foreach ($item->options as $letter => $text)
                                    <li @class(['rounded-xl px-3 py-1.5', 'bg-brand-mint-soft font-semibold text-emerald-900' => strtoupper((string) $item->answer_key) === (string) $letter])><strong>{{ $letter }}</strong> {{ $text }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-3 flex flex-wrap gap-2 text-sm">
                            <span @class(['rounded-lg px-3 py-1', 'bg-brand-mint-soft text-emerald-800' => $item->is_correct, 'bg-brand-rose-soft text-red-800' => ! $item->is_correct])>
                                {{ __('Jawabanmu:') }} <strong>{{ $item->response ?: '–' }}</strong>
                            </span>
                            <span class="rounded-lg bg-brand-mint-soft px-3 py-1 text-emerald-800">{{ __('Kunci:') }} <strong>{{ $item->answer_key }}</strong>@if ($item->accepted_answers !== []) ({{ implode(' / ', $item->accepted_answers) }})@endif</span>
                        </div>

                        @if (filled($item->explanation))
                            <p class="mt-3 text-sm leading-relaxed text-zinc-700">{{ $item->explanation }}</p>
                        @endif

                        <button
                            type="button"
                            x-on:click="$dispatch('tryout-ask-mentor', { code: @js($item->code), label: @js($label) }); document.getElementById('tryout-mentor')?.scrollIntoView({ behavior: 'smooth' })"
                            class="mt-4 rounded-xl bg-brand-teal-soft px-4 py-2 text-sm font-bold text-brand-teal-deep hover:bg-brand-teal/20"
                            data-test="ask-mentor"
                        >
                            {{ __('Tanya mentor soal ini') }}
                        </button>
                    </article>
                @empty
                    <x-ui.empty-state icon="check-badge" :title="__('Tidak ada soal di filter ini')" :description="$wrong_only ? __('Semua jawabanmu di sini benar. Mantap!') : __('Belum ada soal yang dijawab.')" />
                @endforelse
            </div>

            <aside id="tryout-mentor" class="lg:sticky lg:top-6 lg:self-start">
                <livewire:tryout.mentor-chat :attempt_id="$attempt->id" />
            </aside>
        </div>
    @endif

    <p class="text-center text-xs text-zinc-500">{{ config('tryout.disclaimer') }}</p>
</div>

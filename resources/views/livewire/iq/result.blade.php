{{-- Tes IQ result (App\Livewire\Iq\Result). Locked (no paid access): answered count and the call to open access; no
     score, key or explanation is loaded. Unlocked: total percent and category, one row per subtest ("Numerik 5/7 (71%)"),
     the review (choice, key, explanation, "Tanya mentor soal ini"), the material link and the AI mentor chat. Never an
     IQ number; the disclaimer is always shown.
     Data: attempt, test, result (IqResultData), review (IqReviewItemData[], unlocked only), premium_enabled. --}}
<div class="mx-auto flex w-full max-w-5xl flex-col gap-6" data-test="iq-result">
    <a href="{{ route('iq.index') }}" wire:navigate class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-zinc-500 hover:text-brand-teal">
        <x-icons.app-icon name="arrow-left" class="size-4" />
        {{ __('Tes IQ Latihan') }}
    </a>

    @if (! $result->unlocked)
        <section class="flex flex-col items-center gap-4 rounded-4xl border border-brand-teal/10 bg-white p-6 text-center shadow-lift sm:p-10" data-test="iq-result-locked">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-brand-teal-soft text-brand-teal"><x-icons.app-icon name="check-badge" class="size-7" /></span>
            <h1 class="text-2xl font-extrabold text-zinc-900">{{ __('Tes selesai') }}</h1>
            <p class="text-base text-zinc-700" data-test="iq-answered-count">
                {{ __('Kamu sudah mengerjakan :n dari :total soal.', ['n' => $result->answered_count, 'total' => $result->total_questions]) }}
            </p>

            <div class="relative w-full max-w-md overflow-hidden rounded-3xl bg-zinc-100/80">
                <div class="pointer-events-none flex flex-col gap-3 p-6 blur-[3px]" aria-hidden="true">
                    @foreach ([80, 60, 90, 70, 50] as $width)
                        <span class="h-3 rounded-full bg-zinc-300" style="width: {{ $width }}%"></span>
                    @endforeach
                </div>
                <div class="absolute inset-0 flex items-center justify-center gap-2 bg-white/50 text-sm font-bold text-zinc-700">
                    <x-icons.app-icon name="lock-closed" class="size-5 text-brand-orange" />
                    {{ __('Lihat skor dan pembahasan dengan membuka akses') }}
                </div>
            </div>

            <p class="max-w-md text-sm text-zinc-600">{{ __('Dengan akses, kamu bisa melihat skor per subtes, kategori, jawaban benar, pembahasan, materi, dan bertanya ke mentor AI.') }}</p>

            @if ($premium_enabled)
                <x-actions.link-button :href="route('premium')" variant="primary" size="lg" icon="lock-open" data-test="iq-unlock">
                    {{ __('Buka akses') }}
                </x-actions.link-button>
            @else
                <p class="text-sm font-semibold text-zinc-500">{{ __('Akses berbayar belum dibuka. Cek lagi nanti ya.') }}</p>
            @endif
        </section>
    @else
        <section class="relative overflow-hidden rounded-4xl border border-brand-teal/10 bg-white p-6 shadow-lift sm:p-8" data-test="iq-score">
            <div class="pointer-events-none absolute -end-20 -top-24 size-72 rounded-full bg-brand-teal-soft blur-2xl"></div>
            <div class="relative flex flex-col items-center gap-6 sm:flex-row">
                <div class="relative flex size-36 shrink-0 items-center justify-center">
                    <svg viewBox="0 0 120 120" class="size-full -rotate-90" aria-hidden="true">
                        <circle cx="60" cy="60" r="52" fill="none" stroke-width="12" class="stroke-brand-teal-soft" />
                        <circle cx="60" cy="60" r="52" fill="none" stroke-width="12" stroke-linecap="round" class="stroke-brand-teal"
                            stroke-dasharray="{{ round(2 * M_PI * 52, 2) }}" stroke-dashoffset="{{ round(2 * M_PI * 52 * (1 - ($result->score_percent ?? 0) / 100), 2) }}" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-brand-teal-deep">{{ $result->score_percent }}%</span>
                        <span class="text-xs font-semibold text-zinc-500">{{ __(':n/:total benar', ['n' => $result->correct_count, 'total' => $result->total_questions]) }}</span>
                    </div>
                </div>
                <div class="text-center sm:text-start">
                    <span class="inline-flex rounded-full bg-brand-amber-soft px-3 py-1 text-xs font-bold text-amber-900">{{ __('Skor latihan') }}</span>
                    <h1 class="mt-3 text-2xl font-extrabold text-zinc-900 sm:text-3xl" data-test="iq-category">{{ $result->category }}</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        {{ __('Kamu menjawab :answered dari :total soal. Status: :status.', ['answered' => $result->answered_count, 'total' => $result->total_questions, 'status' => $result->status_label]) }}
                    </p>
                </div>
            </div>
        </section>

        <x-ui.card padding="md" data-test="iq-subtests">
            <h2 class="text-base font-extrabold text-zinc-900">{{ __('Skor per subtes') }}</h2>
            <ul class="mt-4 flex flex-col gap-4">
                @foreach ($result->subtests as $subtest)
                    <li data-test="iq-subtest-{{ $subtest->subtest }}">
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="flex items-center gap-2 font-semibold text-zinc-800">
                                <x-icons.app-icon :name="\App\Enums\IqSubtest::from($subtest->subtest)->icon()" class="size-4 text-brand-teal" />
                                {{ $subtest->label }}
                            </span>
                            <span class="font-extrabold text-zinc-900">{{ $subtest->correct }}/{{ $subtest->total }} ({{ $subtest->percent }}%)</span>
                        </div>
                        <span class="mt-1.5 block h-2 overflow-hidden rounded-full bg-zinc-100">
                            <span class="block h-full rounded-full bg-brand-teal-light" style="width: {{ $subtest->percent }}%"></span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <section class="flex flex-col gap-3" data-test="iq-review">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-extrabold text-zinc-900">{{ __('Pembahasan') }}</h2>
                    <a href="{{ route('iq.material') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full bg-brand-teal-soft px-3 py-1.5 text-sm font-bold text-brand-teal-deep hover:bg-brand-teal/20" data-test="iq-material-link">
                        <x-icons.app-icon name="presentation-chart-bar" class="size-4" /> {{ __('Materi Tes IQ') }}
                    </a>
                </div>

                @foreach ($review as $item)
                    <details class="rounded-3xl border border-brand-teal/10 bg-white p-4 shadow-soft" wire:key="iq-review-{{ $item->position }}">
                        <summary class="flex cursor-pointer list-none items-center gap-3">
                            <span @class([
                                'flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-extrabold',
                                'bg-brand-mint-soft text-emerald-800' => $item->is_correct,
                                'bg-brand-rose-soft text-red-700' => ! $item->is_correct,
                            ])>{{ $item->position }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm font-semibold text-zinc-800">{{ $item->prompt }}</span>
                            <span class="shrink-0 text-xs font-bold {{ $item->is_correct ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ $item->choice === null ? __('Tidak dijawab') : ($item->is_correct ? __('Benar') : __('Salah')) }}
                            </span>
                        </summary>

                        <div class="mt-4 flex flex-col gap-3 text-sm text-zinc-700">
                            <span class="w-fit rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-bold text-zinc-600">{{ $item->subtest_label }}</span>
                            @if ($item->passage)
                                <p class="rounded-2xl bg-brand-cream px-4 py-3 whitespace-pre-line">{{ $item->passage }}</p>
                            @endif
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="{{ $item->image_alt }}" loading="lazy" class="max-h-80 w-full rounded-2xl border border-zinc-100 object-contain">
                            @endif
                            <p class="font-semibold whitespace-pre-line text-zinc-900">{{ $item->prompt }}</p>
                            <ul class="flex flex-col gap-1.5">
                                @foreach ($item->options as $letter => $text)
                                    <li @class([
                                        'flex items-center gap-2 rounded-xl px-3 py-2',
                                        'bg-brand-mint-soft font-semibold text-emerald-900' => $letter === $item->answer_key,
                                        'bg-brand-rose-soft text-red-800' => $letter === $item->choice && $letter !== $item->answer_key,
                                        'bg-zinc-50' => $letter !== $item->answer_key && $letter !== $item->choice,
                                    ])>
                                        <span class="font-extrabold">{{ $letter }}.</span> {{ $text }}
                                        @if ($letter === $item->answer_key)
                                            <span class="ms-auto text-xs font-bold">{{ __('Jawaban benar') }}</span>
                                        @elseif ($letter === $item->choice)
                                            <span class="ms-auto text-xs font-bold">{{ __('Jawabanmu') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            <p class="rounded-2xl bg-brand-teal-soft/60 px-4 py-3 text-zinc-800">
                                <span class="font-bold">{{ __('Pembahasan:') }}</span>
                                {{ $item->explanation ?? __('Pembahasan soal ini sedang disiapkan. Kamu bisa menanyakannya ke mentor AI.') }}
                            </p>
                            <button type="button" x-on:click="$dispatch('iq-ask-mentor', { position: {{ $item->position }} }); document.getElementById('iq-mentor')?.scrollIntoView({ behavior: 'smooth' })" class="flex w-fit items-center gap-1.5 text-sm font-bold text-brand-teal hover:text-brand-teal-deep" data-test="iq-ask-mentor">
                                <x-icons.app-icon name="sparkles" class="size-4" /> {{ __('Tanya mentor soal ini') }}
                            </button>
                        </div>
                    </details>
                @endforeach
            </section>

            <aside id="iq-mentor" class="scroll-mt-6 lg:sticky lg:top-6 lg:self-start">
                <livewire:iq.mentor-chat :attempt_id="$attempt->id" />
            </aside>
        </div>
    @endif

    <p class="rounded-3xl bg-brand-amber-soft px-5 py-4 text-center text-sm font-semibold text-amber-900" data-test="iq-disclaimer">{{ config('iq.disclaimer') }}</p>
</div>

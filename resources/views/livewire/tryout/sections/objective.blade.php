{{-- Listening / Reading subtest (App\Livewire\Tryout\Sections\ObjectiveSection). Listening: sticky audio card,
     attachment table/map, then one question. Reading: passage panel (55%) beside the question (45%); on phones a
     "Teks | Soal" tab switch. Answers lock once sent, so the number panel only shows progress (D9).
     Data: question (view array, no key), answered, total, skill, type, mode, show_block_intro + header data. --}}
@php
    $is_reading = $skill === \App\Enums\TryoutSkill::Reading;
    $unit = $question['unit'] ?? null;
    $sequence = $question['sequence'] ?? ($answered + 1);
    $progress = $total > 0 ? (int) round($answered / $total * 100) : 0;
    $unanswered = max(0, $total - $answered);
@endphp

<div x-data="{ tab: 'soal' }" x-on:tryout-question-changed.window="if ($event.detail.new_block) { tab = 'teks'; $refs.passage?.scrollTo({ top: 0, behavior: 'smooth' }) }">
    <x-tryout.header
        :title="$tryout_code.' · '.$skill->getLabel()"
        :mobile-title="$skill->getLabel().' · '.min($sequence, $total).'/'.$total"
        :progress-label="__('Soal :n dari :total', ['n' => min($sequence, $total), 'total' => $total])"
        :progress="$progress"
        :deadline="$deadline"
        :server-now="$server_now"
        :grace="$grace"
        :skills="$skills"
        :finish-label="__('Selesaikan subtes')"
        :confirm-text="$unanswered > 0
            ? __('Masih ada :n soal yang belum dijawab. Soal yang belum dijawab dihitung 0.', ['n' => $unanswered])
            : __('Semua soal sudah dijawab. Kirim subtes ini?')"
    />

    @if ($question === null)
        <div class="flex min-h-[50vh] items-center justify-center">
            <x-icons.app-icon name="spinner" class="size-8 animate-spin text-brand-teal" />
        </div>
    @elseif ($show_block_intro && $unit)
        {{-- New Listening block: ready card, audio never autoplays --}}
        <div class="mx-auto flex max-w-xl flex-col items-center gap-5 px-4 py-16 text-center" data-test="block-intro">
            <span class="rounded-full bg-brand-teal-soft px-4 py-1.5 text-sm font-extrabold text-brand-teal-deep">
                {{ __('Bagian :n dari :total', ['n' => $unit['block_number'], 'total' => $unit['block_total']]) }}
            </span>
            <h1 class="text-2xl font-extrabold text-zinc-900 sm:text-3xl">{{ $unit['title'] }}</h1>
            @if (filled($unit['context']))
                <p class="text-base text-zinc-600">{{ $unit['context'] }}</p>
            @endif
            <p class="text-sm text-zinc-500">
                {{ $mode === \App\Enums\TryoutMode::Exam
                    ? __('Mode ujian: audio hanya bisa diputar sekali. Siapkan headset dan baca soal pertama dulu.')
                    : __('Mode latihan: audio bisa diputar maksimal :n kali.', ['n' => $unit['plays_allowed']]) }}
            </p>
            <button type="button" wire:click="dismissBlockIntro" class="h-14 w-full max-w-xs rounded-2xl bg-brand-teal text-base font-extrabold text-white shadow-soft hover:bg-brand-teal-deep" data-test="begin-block">
                {{ __('Saya siap') }}
            </button>
        </div>
    @else
        @if ($is_reading)
            {{-- Phone tabs --}}
            <div class="sticky top-29 z-30 grid grid-cols-2 gap-2 bg-white px-4 py-3 shadow-soft lg:hidden">
                <button type="button" x-on:click="tab = 'teks'" :class="tab === 'teks' ? 'bg-brand-teal text-white' : 'bg-zinc-100 text-zinc-700'" class="h-11 rounded-xl text-sm font-bold">{{ __('Teks') }}</button>
                <button type="button" x-on:click="tab = 'soal'" :class="tab === 'soal' ? 'bg-brand-teal text-white' : 'bg-zinc-100 text-zinc-700'" class="h-11 rounded-xl text-sm font-bold">{{ __('Soal') }}</button>
            </div>
        @endif

        <div @class([
            'mx-auto w-full max-w-7xl gap-6 px-4 py-6 sm:px-6',
            'grid lg:grid-cols-[55fr_45fr]' => $is_reading,
            'grid lg:grid-cols-[1fr_18rem]' => ! $is_reading,
        ])>
            @if ($is_reading && $unit)
                <article
                    x-ref="passage"
                    :class="tab === 'teks' ? 'block' : 'hidden lg:block'"
                    class="rounded-4xl border border-brand-teal/10 bg-white p-6 shadow-soft lg:sticky lg:top-32 lg:max-h-[calc(100vh-9rem)] lg:overflow-y-auto sm:p-8"
                    wire:key="passage-{{ $unit['code'] }}"
                    data-test="reading-passage"
                >
                    <p class="text-xs font-extrabold tracking-wide text-brand-teal uppercase">{{ __('Teks :n dari :total', ['n' => $unit['block_number'], 'total' => $unit['block_total']]) }}</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-zinc-900">{{ $unit['title'] }}</h2>
                    <div class="mt-4 text-[17px] leading-[1.7] text-zinc-800">
                        <x-learning.rich-content :content="$unit['passage']" />
                    </div>
                </article>
            @endif

            <div @if ($is_reading) :class="tab === 'teks' ? 'hidden lg:flex' : 'flex'" @endif class="flex flex-col gap-5">
                @if (! $is_reading && $unit)
                    <div class="sticky top-29 z-20">
                        <x-tryout.audio-player :unit="$unit" :label="__('Bagian :n · Audio', ['n' => $unit['block_number']])" />
                    </div>

                    @if (! empty($unit['attachment']))
                        <x-tryout.data-table :table="$unit['attachment']" class="shadow-soft" />
                    @endif

                    @if (filled($unit['image_url']))
                        <img src="{{ $unit['image_url'] }}" alt="{{ __('Peta atau gambar soal') }}" class="w-full rounded-3xl border border-brand-teal/10 bg-white object-contain shadow-soft">
                    @endif
                @endif

                <section class="rounded-4xl border border-brand-teal/10 bg-white p-6 shadow-soft sm:p-7" wire:key="question-{{ $question['code'] }}" data-test="question-card">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-extrabold text-brand-teal">{{ __('Soal :n', ['n' => $sequence]) }}</span>
                        <span class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                            {{ $question['source_type'] ?: ($type?->hasOptions() ? __('Pilih satu jawaban') : __('Isi jawaban')) }}
                        </span>
                    </div>

                    <h3 class="mt-3 text-lg leading-snug font-extrabold text-zinc-900 sm:text-xl">{{ $question['prompt'] }}</h3>

                    <div class="mt-5">
                        @if ($type?->hasOptions())
                            <x-tryout.choice-options :options="$question['options']" />
                        @else
                            <label class="block">
                                <span class="sr-only">{{ __('Jawabanmu') }}</span>
                                <input
                                    type="text"
                                    wire:model="answer"
                                    wire:keydown.enter="submit"
                                    autocomplete="off"
                                    class="h-14 w-full rounded-2xl border-2 border-zinc-200 px-4 text-base font-medium focus:border-brand-teal focus:ring-brand-teal/20"
                                    placeholder="{{ __('Tulis jawabanmu') }}"
                                    data-test="short-answer"
                                >
                            </label>
                            @if ($question['word_limit'])
                                <p class="mt-2 text-xs text-zinc-500">{{ __('Maksimal :n kata.', ['n' => $question['word_limit']]) }}</p>
                            @endif
                        @endif
                    </div>

                    @error('answer')
                        <p class="mt-3 text-sm font-semibold text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-6 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-center gap-1.5 text-xs text-zinc-500">
                            <x-icons.app-icon name="lock-closed" class="size-3.5" />
                            {{ __('Jawaban terkunci setelah dikirim') }}
                        </p>
                        <button
                            type="button"
                            wire:click="submit"
                            wire:loading.attr="disabled"
                            class="h-12 rounded-2xl bg-brand-teal px-8 text-base font-extrabold text-white shadow-soft transition hover:bg-brand-teal-deep disabled:bg-zinc-200 disabled:text-zinc-500 disabled:shadow-none"
                            data-test="submit-answer"
                        >
                            <span wire:loading.remove wire:target="submit">{{ __('Kirim dan lanjut') }}</span>
                            <span wire:loading wire:target="submit">{{ __('Mengirim...') }}</span>
                        </button>
                    </div>
                </section>

                @if ($is_reading)
                    <button type="button" x-on:click="tab = 'teks'; window.scrollTo({ top: 0 })" class="w-fit rounded-full bg-brand-amber-soft px-5 py-2.5 text-sm font-bold text-zinc-800 lg:hidden">
                        {{ __('Lihat teks') }}
                    </button>
                @endif

                @if ($is_reading)
                    @include('livewire.tryout.sections.partials.progress-grid', ['answered' => $answered, 'sequence' => $sequence, 'total' => $total, 'columns' => 10])
                @endif
            </div>

            @unless ($is_reading)
                <aside class="flex flex-col gap-4 lg:sticky lg:top-32 lg:self-start">
                    @include('livewire.tryout.sections.partials.progress-grid', ['answered' => $answered, 'sequence' => $sequence, 'total' => $total, 'columns' => 8])
                    <div class="rounded-3xl bg-brand-teal-soft p-5">
                        <p class="text-sm font-extrabold text-brand-teal-deep">{{ __('Kenapa tidak bisa kembali?') }}</p>
                        <p class="mt-1 text-sm text-zinc-700">{{ __('Soal berikutnya dipilih dari jawabanmu, jadi soal yang sudah dikirim tidak bisa diubah.') }}</p>
                    </div>
                </aside>
            @endunless
        </div>
    @endif
</div>

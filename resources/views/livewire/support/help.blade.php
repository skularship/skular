{{--
    "Bantuan" page (App\Livewire\Support\Help): quick help through WhatsApp, a form to report bugs,
    errors or other problems (stored as SupportTicket, reviewed in Filament "Tiket bantuan"),
    and the mentee's five most recent tickets with their status label. Admin notes are never shown here.
--}}
<div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <x-ui.section-heading
        :eyebrow="__('Pusat bantuan')"
        eyebrow-icon="lifebuoy"
        level="1"
        :title="__('Bantuan')"
        :description="__('Ada yang error, tidak bisa dibuka, atau terasa janggal? Ceritakan ke kami. Untuk hal mendesak, chat tim Skular lewat WhatsApp.')"
    />

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Report form --}}
        <x-ui.card class="flex flex-col gap-5 lg:col-span-2" data-test="support-form-card">
            <div class="flex items-start gap-3">
                <x-ui.icon-badge icon="bug-ant" tone="peach" />
                <div>
                    <h2 class="text-lg font-bold text-zinc-900">{{ __('Laporkan masalah') }}</h2>
                    <p class="text-sm text-zinc-600">{{ __('Makin jelas ceritamu, makin cepat kami bisa memperbaikinya.') }}</p>
                </div>
            </div>

            @error('ticket')
                <x-feedback.callout variant="warning" :heading="__('Laporan belum terkirim')" data-test="support-error">
                    {{ $message }}
                </x-feedback.callout>
            @enderror

            <form wire:submit="submit" class="flex flex-col gap-4" data-test="support-form">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-forms.select wire:model="category" :label="__('Jenis masalah')" required>
                        <option value="">{{ __('Pilih jenis masalah') }}</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-forms.select>

                    <x-forms.input
                        wire:model="subject"
                        :label="__('Judul singkat')"
                        :placeholder="__('Contoh: Tombol simpan tidak merespons')"
                        maxlength="150"
                        required
                    />
                </div>

                <div class="w-full">
                    <label for="support-description" class="mb-1.5 block text-sm font-medium text-zinc-700">{{ __('Apa yang terjadi?') }}</label>
                    <textarea
                        id="support-description"
                        wire:model="description"
                        rows="5"
                        maxlength="2000"
                        required
                        placeholder="{{ __("Ceritakan langkah yang kamu lakukan, apa yang kamu harapkan, dan apa yang muncul. Contoh:\n1. Buka modul Esai Motivasi\n2. Klik Submit Jawaban\n3. Muncul pesan error") }}"
                        @class([
                            'block w-full rounded-lg border bg-white px-3 py-2 text-base text-zinc-700 placeholder:text-zinc-400 focus:outline-hidden focus:ring-2 focus:ring-accent sm:text-sm',
                            'border-red-500' => $errors->has('description'),
                            'border-zinc-200' => ! $errors->has('description'),
                        ])
                    ></textarea>
                    @error('description')
                        <p class="mt-1.5 text-sm text-red-600" data-test="error-description">{{ $message }}</p>
                    @enderror
                </div>

                <x-forms.input
                    type="url"
                    wire:model="page_url"
                    :label="__('Tautan halaman yang bermasalah (opsional)')"
                    placeholder="https://skularship.com/..."
                    maxlength="2048"
                />

                <div class="w-full">
                    <label for="support-screenshot" class="mb-1.5 block text-sm font-medium text-zinc-700">{{ __('Tangkapan layar (opsional)') }}</label>

                    @if ($screenshot && ! $errors->has('screenshot'))
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-brand-teal/25 bg-brand-teal/5 px-4 py-3 text-sm text-zinc-700">
                            <span class="inline-flex min-w-0 items-center gap-2">
                                <x-icons.app-icon name="photo" class="size-4 shrink-0 text-brand-teal" />
                                <span class="truncate">{{ __('Gambar siap dikirim') }}</span>
                            </span>
                            <button type="button" wire:click="removeScreenshot" class="inline-flex min-h-9 items-center rounded-lg px-2 text-xs font-semibold text-red-600 hover:underline">
                                {{ __('Hapus') }}
                            </button>
                        </div>
                    @else
                        <input
                            id="support-screenshot"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            wire:model="screenshot"
                            class="block w-full cursor-pointer rounded-lg border border-dashed border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-teal-soft file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-brand-teal-deep hover:border-brand-teal"
                        >
                    @endif

                    <p class="mt-1 text-xs text-zinc-500" wire:loading.remove wire:target="screenshot">{{ __('JPG, PNG, atau WebP, maksimal 5 MB. Hanya dilihat tim Skular.') }}</p>
                    <p class="mt-1 text-xs text-brand-teal" wire:loading wire:target="screenshot">{{ __('Mengunggah gambar…') }}</p>

                    @error('screenshot')
                        <p class="mt-1.5 text-sm text-red-600" data-test="error-screenshot">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-3 border-t border-zinc-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-zinc-500">{{ __('Kami juga mencatat jenis browser-mu supaya masalahnya lebih mudah dilacak.') }}</p>

                    <x-actions.button type="submit" icon="paper-airplane" wire:loading.attr="disabled" wire:target="screenshot" data-test="submit-support">
                        {{ __('Kirim laporan') }}
                    </x-actions.button>
                </div>
            </form>
        </x-ui.card>

        <aside class="flex flex-col gap-4">
            {{-- WhatsApp help line --}}
            @if ($whatsapp_url)
                <div class="flex flex-col gap-4 rounded-3xl border border-emerald-200 bg-brand-mint-soft/70 p-5 shadow-soft" data-test="support-whatsapp">
                    <div class="flex items-start gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm" aria-hidden="true">
                            <x-icons.app-icon name="chat-bubble-left-right" class="size-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-zinc-900">{{ __('Chat via WhatsApp') }}</h2>
                            <p class="text-sm text-zinc-600">{{ __('Butuh jawaban cepat? Tim Skular siap membantu di jam kerja.') }}</p>
                        </div>
                    </div>

                    <p class="text-lg font-extrabold tracking-tight text-zinc-900">{{ $whatsapp_number }}</p>

                    <a
                        href="{{ $whatsapp_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"
                        data-test="support-whatsapp-link"
                    >
                        <x-icons.app-icon name="arrow-top-right-on-square" class="size-4" />
                        {{ __('Buka WhatsApp') }}
                    </a>
                </div>
            @endif

            {{-- Tips --}}
            <x-ui.card padding="sm" class="flex flex-col gap-3">
                <h2 class="inline-flex items-center gap-2 text-sm font-bold text-zinc-900">
                    <x-icons.app-icon name="light-bulb" class="size-4 text-brand-gold" />
                    {{ __('Biar laporanmu cepat ditangani') }}
                </h2>
                <ul class="flex flex-col gap-2 text-sm text-zinc-600">
                    <li class="flex items-start gap-2"><x-icons.app-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-teal" />{{ __('Tulis langkah yang kamu lakukan sebelum masalah muncul.') }}</li>
                    <li class="flex items-start gap-2"><x-icons.app-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-teal" />{{ __('Lampirkan tangkapan layar pesan error-nya.') }}</li>
                    <li class="flex items-start gap-2"><x-icons.app-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-teal" />{{ __('Jawaban latihanmu tetap aman tersimpan, jadi tidak perlu khawatir.') }}</li>
                </ul>
            </x-ui.card>

            {{-- Own recent tickets --}}
            <x-ui.card padding="sm" class="flex flex-col gap-3" data-test="support-history">
                <h2 class="text-sm font-bold text-zinc-900">{{ __('Laporanmu') }}</h2>

                @forelse ($tickets as $ticket)
                    <div class="flex items-start justify-between gap-3 border-t border-zinc-100 pt-3 first-of-type:border-0 first-of-type:pt-0" wire:key="ticket-{{ $ticket->id }}">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold break-words text-zinc-800">{{ $ticket->subject }}</p>
                            <p class="text-xs text-zinc-500">{{ $ticket->created_at?->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                        <x-ui.pill :variant="$ticket->status->pillVariant()" class="shrink-0">{{ $ticket->status->label() }}</x-ui.pill>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">{{ __('Belum ada laporan. Semoga semuanya lancar ya!') }}</p>
                @endforelse
            </x-ui.card>
        </aside>
    </div>
</div>

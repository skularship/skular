{{-- "Vault Dokumen" (App\Livewire\Vault\Index): private file storage and links to each scholarship checklist. --}}
<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 py-6">
    <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900">{{ __('Vault Dokumen') }}</h1>

    @if ($can_upload)
        <x-ui.card class="flex flex-col gap-4" data-test="vault-upload">
            <h2 class="font-bold text-zinc-900">{{ __('Tambah dokumen') }}</h2>
            <form wire:submit="save" class="flex flex-col gap-3">
                <div>
                    <label for="vault-title" class="text-sm font-semibold text-zinc-700">{{ __('Nama dokumen') }}</label>
                    <input id="vault-title" type="text" wire:model="title" placeholder="{{ __('Contoh: Transkrip semester 5') }}" class="mt-1 w-full rounded-2xl border border-zinc-200 px-4 py-2.5" />
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="vault-file" class="text-sm font-semibold text-zinc-700">{{ __('File') }}</label>
                    <input id="vault-file" type="file" wire:model="file" class="mt-1 w-full text-sm" />
                    @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-zinc-500">{{ __('PDF, JPG, PNG, DOC, DOCX. Maksimal :mb MB, sampai :n dokumen.', ['mb' => (int) config('plans.vault.max_kb') / 1024, 'n' => $max_files]) }}</p>
                </div>
                <x-actions.button type="submit" class="w-fit">{{ __('Simpan ke vault') }}</x-actions.button>
            </form>
        </x-ui.card>
    @else
        <x-ui.card class="flex flex-col gap-3" data-test="vault-locked">
            <div class="flex items-center gap-2">
                <x-ui.pill variant="peach">{{ __('Pro') }}</x-ui.pill>
                <p class="font-bold text-zinc-900">{{ __('Simpan semua berkasmu di satu tempat') }}</p>
            </div>
            <p class="text-zinc-600">{{ __('Dengan Pro, kamu bisa menyimpan transkrip, CV, esai, dan surat rekomendasi di sini, lalu menempelkannya ke checklist tiap beasiswa.') }}</p>
            <x-actions.link-button :href="route('premium')" wire:navigate class="w-fit">{{ __('Lihat paket Pro') }}</x-actions.link-button>
        </x-ui.card>
    @endif

    <x-ui.card padding="none" class="overflow-hidden" data-test="vault-list">
        <h2 class="border-b border-zinc-100 px-5 py-4 text-sm font-bold tracking-wide text-zinc-500 uppercase">{{ __('Dokumenmu') }}</h2>
        @forelse ($documents as $document)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 last:border-b-0">
                <div>
                    <p class="font-semibold text-zinc-900">{{ $document->title }}</p>
                    <p class="text-xs text-zinc-500">{{ $document->original_name }} &middot; {{ $document->size_kb }} KB</p>
                </div>
                <div class="flex items-center gap-3 text-sm font-semibold">
                    <a href="{{ route('vault.download', $document) }}" class="text-brand-teal hover:underline">{{ __('Unduh') }}</a>
                    <button type="button" wire:click="delete({{ $document->id }})" wire:confirm="{{ __('Hapus dokumen ini?') }}" class="text-red-600 hover:underline">{{ __('Hapus') }}</button>
                </div>
            </div>
        @empty
            <p class="px-5 py-6 text-sm text-zinc-500">{{ __('Belum ada dokumen.') }}</p>
        @endforelse
    </x-ui.card>

    @if ($applications->isNotEmpty())
        <x-ui.card padding="none" class="overflow-hidden" data-test="vault-checklists">
            <h2 class="border-b border-zinc-100 px-5 py-4 text-sm font-bold tracking-wide text-zinc-500 uppercase">{{ __('Checklist berkas per beasiswa') }}</h2>
            @foreach ($applications as $application)
                <a href="{{ route('checklist.show', $application) }}" wire:navigate class="flex items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 last:border-b-0 hover:bg-brand-cream">
                    <span class="font-semibold text-zinc-900">{{ $application->scholarship->scholarship_name }}</span>
                    <span class="text-sm font-semibold text-brand-teal">{{ __('Buka checklist') }}</span>
                </a>
            @endforeach
        </x-ui.card>
    @endif
</div>

{{-- "Checklist Berkas" (App\Livewire\Checklist\Show): per-scholarship document checklist, locked for Gratis. --}}
<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 py-6">
    <div>
        <a href="{{ route('vault.index') }}" wire:navigate class="text-sm font-semibold text-brand-teal hover:underline">{{ __('Kembali ke Vault') }}</a>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-zinc-900">{{ __('Checklist berkas') }}</h1>
        <p class="text-zinc-600">{{ $application->scholarship->scholarship_name }}</p>
    </div>

    @if (! $unlocked)
        <x-ui.card class="flex flex-col gap-3" data-test="checklist-locked">
            <div class="flex items-center gap-2">
                <x-ui.pill variant="peach">{{ __('Pro') }}</x-ui.pill>
                <p class="font-bold text-zinc-900">{{ __('Pantau kelengkapan berkas tiap beasiswa') }}</p>
            </div>
            <p class="text-zinc-600">{{ __('Tandai berkas yang sudah siap, tempel dokumen dari vault, dan tambah berkas sesuai persyaratan beasiswanya.') }}</p>
            <x-actions.link-button :href="route('premium')" wire:navigate class="w-fit">{{ __('Lihat paket Pro') }}</x-actions.link-button>
        </x-ui.card>
    @else
        <x-ui.card padding="none" class="overflow-hidden" data-test="checklist-items">
            @foreach ($items as $item)
                <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 px-5 py-4 last:border-b-0" wire:key="item-{{ $item->id }}">
                    <input type="checkbox" @checked($item->is_done) wire:click="toggle({{ $item->id }})" class="size-5 rounded" aria-label="{{ $item->label }}" />
                    <span @class(['flex-1 font-semibold', 'text-zinc-400 line-through' => $item->is_done, 'text-zinc-900' => ! $item->is_done])>{{ $item->label }}</span>
                    <select wire:change="attach({{ $item->id }}, $event.target.value === '' ? null : Number($event.target.value))" class="rounded-xl border border-zinc-200 px-3 py-1.5 text-sm" aria-label="{{ __('Dokumen untuk :label', ['label' => $item->label]) }}">
                        <option value="">{{ __('Tanpa dokumen') }}</option>
                        @foreach ($documents as $document)
                            <option value="{{ $document->id }}" @selected($item->vault_document_id === $document->id)>{{ $document->title }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="remove({{ $item->id }})" class="text-sm font-semibold text-red-600 hover:underline">{{ __('Hapus') }}</button>
                </div>
            @endforeach
        </x-ui.card>

        <form wire:submit="add" class="flex flex-wrap items-start gap-3">
            <div class="min-w-0 flex-1">
                <input type="text" wire:model="new_label" placeholder="{{ __('Tambah berkas lain') }}" class="w-full rounded-2xl border border-zinc-200 px-4 py-2.5" aria-label="{{ __('Nama berkas') }}" />
                @error('new_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <x-actions.button type="submit">{{ __('Tambah') }}</x-actions.button>
        </form>
    @endif
</div>

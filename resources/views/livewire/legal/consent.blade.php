{{-- Consent screen (App\Livewire\Legal\Consent): the two statements from the sign-up form, shown before the app
     when the mentee has not accepted the current Terms and Privacy Policy. Data: $missing (LegalDocument list),
     $is_first_time (no consent logged yet, e.g. a new Google account). --}}
<div class="flex flex-col gap-6" data-test="legal-consent">
    <x-auth.auth-header
        :title="$is_first_time ? __('Satu langkah lagi') : __('Ada pembaruan dokumen')"
        :description="$is_first_time
            ? __('Sebelum mulai, baca dan setujui Syarat & Ketentuan dan Kebijakan Privasi Skular.')
            : __('Kami memperbarui dokumen di bawah. Baca perubahannya, lalu setujui untuk melanjutkan.')"
    />

    <ul class="flex flex-col gap-2 text-sm">
        @foreach ($missing as $legalDocument)
            <li>
                <a href="{{ route($legalDocument->routeName()) }}" target="_blank" rel="noopener" class="font-semibold text-brand-teal underline underline-offset-2">
                    {{ $legalDocument->label() }}
                </a>
                <span class="text-zinc-500">{{ __('(versi :version)', ['version' => $legalDocument->version()]) }}</span>
            </li>
        @endforeach
    </ul>

    <form wire:submit="accept" class="flex flex-col gap-4">
        <div>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="terms" class="mt-0.5 size-4 shrink-0 rounded border-zinc-300 accent-zinc-800" data-test="consent-terms" />
                <span class="text-zinc-700">
                    {{ __('Aku setuju dengan') }}
                    <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-semibold text-brand-teal underline underline-offset-2">{{ __('Syarat & Ketentuan') }}</a>
                    {{ __('dan') }}
                    <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="font-semibold text-brand-teal underline underline-offset-2">{{ __('Kebijakan Privasi') }}</a>,
                    {{ __('termasuk pemrosesan tulisanku oleh penyedia AI di luar Indonesia.') }}
                </span>
            </label>
            @error('terms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="age_confirmation" class="mt-0.5 size-4 shrink-0 rounded border-zinc-300 accent-zinc-800" data-test="consent-age" />
                <span class="text-zinc-700">{{ __('Aku 18 tahun ke atas, atau orang tua/waliku sudah mengizinkan aku memakai Skular.') }}</span>
            </label>
            @error('age_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-actions.button type="submit" class="w-full" data-test="consent-accept-button">
            {{ __('Setuju dan lanjutkan') }}
        </x-actions.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center text-sm text-zinc-600">
        @csrf
        <span>{{ __('Tidak setuju?') }}</span>
        <button type="submit" class="font-semibold text-brand-teal underline underline-offset-2">{{ __('Keluar') }}</button>
    </form>
</div>

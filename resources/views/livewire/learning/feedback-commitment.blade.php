{{-- Free-access commitment (App\Livewire\Learning\FeedbackCommitment): one goal, one large "Saya setuju" button. Modules stay closed until the mentee agrees. --}}
<div class="mx-auto flex w-full max-w-2xl flex-col gap-6 py-4 sm:py-10">
    <section class="sk-rise rounded-4xl border-2 border-brand-teal/25 bg-white p-6 shadow-soft sm:p-10 dark:bg-zinc-800/60" data-test="feedback-commitment">
        <x-ui.icon-badge icon="sparkles" tone="amber" size="lg" solid />

        <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
            {{ __('Kamu masuk program akses gratis Skular') }}
        </h1>

        <div class="mt-4 flex flex-col gap-3 text-lg leading-relaxed text-zinc-700 dark:text-zinc-300">
            <p>{{ __('Gratisnya eksklusif: sebagai gantinya, kamu memberi masukan jujur di akhir setiap modul.') }}</p>
            <p>{{ __('Masukanmu langsung kami pakai untuk memperbaiki Skular.') }}</p>
        </div>

        <ul class="mt-6 flex flex-col gap-3 rounded-3xl bg-brand-teal/6 p-5 text-base text-zinc-700 dark:bg-white/5 dark:text-zinc-300">
            <li class="flex items-start gap-3">
                <x-icons.app-icon name="check-circle" class="mt-0.5 size-6 shrink-0 text-brand-teal" />
                {{ __('Cukup 1 sampai 2 menit di akhir modul.') }}
            </li>
            <li class="flex items-start gap-3">
                <x-icons.app-icon name="check-circle" class="mt-0.5 size-6 shrink-0 text-brand-teal" />
                {{ __('Modul berikutnya terbuka setelah masukanmu terkirim.') }}
            </li>
        </ul>

        <div class="mt-8 flex flex-col gap-3">
            <x-actions.button
                type="button"
                size="lg"
                icon="hand-thumb-up"
                wire:click="accept"
                class="w-full"
                data-test="accept-commitment"
            >
                {{ __('Saya setuju') }}
            </x-actions.button>

            <a href="{{ $later_url }}" wire:navigate class="flex min-h-11 items-center justify-center text-base font-semibold text-zinc-600 underline-offset-4 hover:underline dark:text-zinc-300">
                {{ __('Nanti saja') }}
            </a>
        </div>
    </section>
</div>

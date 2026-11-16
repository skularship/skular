{{--
    Cookie consent banner (Alpine "cookieConsent", resources/js/cookie-consent.js).

    Included at the top of <body> in every public, auth and app layout. It sits
    in the normal page flow (never fixed or overlaid), so on a phone it pushes
    the page down instead of covering the pay button or the login form.
    Shown on the first visit, or when the cookie policy version changes; any
    element with data-cookie-settings (footer link "Pengaturan cookie") reopens it.
    "Terima semua" and "Tolak semua" have the same visual weight.
--}}
<section
    x-data="cookieConsent"
    x-cloak
    x-show="open"
    x-on:skular:cookie-settings.window="reopen()"
    role="region"
    aria-labelledby="cookie-consent-title"
    data-test="cookie-consent"
    class="relative z-[60] border-b border-brand-teal/15 bg-white text-zinc-700"
>
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-3 px-4 py-4 sm:px-6">
        <div>
            <h2 id="cookie-consent-title" x-ref="title" tabindex="-1" class="text-sm font-bold text-brand-ink focus:outline-hidden">
                {{ __('Pengaturan cookie') }}
            </h2>
            <p class="mt-1 text-sm leading-relaxed">
                {{ __('Kami memakai cookie esensial agar login dan keamanan berjalan. Dengan izinmu, kami juga memakai Google Analytics untuk menghitung kunjungan. Kamu bisa mengubah pilihan ini kapan saja.') }}
                <a href="{{ route('legal.cookies') }}" class="font-semibold text-brand-teal underline underline-offset-2">{{ __('Kebijakan Cookie') }}</a>
            </p>
        </div>

        <div x-show="settingsOpen" class="grid gap-2 rounded-xl border border-brand-teal/15 bg-warm-cream/60 p-3 text-sm">
            <div class="flex items-start gap-3">
                <input type="checkbox" id="cookie-essential" checked disabled class="mt-1 size-4 rounded border-zinc-300">
                <label for="cookie-essential">
                    <span class="font-semibold text-brand-ink">{{ __('Esensial') }}</span>
                    <span class="block text-zinc-600">{{ __('Selalu aktif. Dipakai untuk sesi login, keamanan formulir, dan menyimpan pilihan cookie ini.') }}</span>
                </label>
            </div>
            <div class="flex items-start gap-3">
                <input type="checkbox" id="cookie-analytics" x-model="analyticsChecked" class="mt-1 size-4 rounded border-zinc-300 text-brand-teal" data-test="cookie-analytics-toggle">
                <label for="cookie-analytics">
                    <span class="font-semibold text-brand-ink">{{ __('Analitik (Google Analytics)') }}</span>
                    <span class="block text-zinc-600">{{ __('Opsional. Menghitung halaman yang dikunjungi supaya kami tahu bagian mana yang perlu diperbaiki.') }}</span>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" x-on:click="acceptAll()" data-test="cookie-accept" class="min-h-11 flex-1 rounded-xl border border-brand-ink bg-brand-ink px-4 text-sm font-semibold text-white sm:flex-none">
                {{ __('Terima semua') }}
            </button>
            <button type="button" x-on:click="rejectAll()" data-test="cookie-reject" class="min-h-11 flex-1 rounded-xl border border-brand-ink bg-brand-ink px-4 text-sm font-semibold text-white sm:flex-none">
                {{ __('Tolak semua') }}
            </button>
            <button type="button" x-show="! settingsOpen" x-on:click="settingsOpen = true" class="min-h-11 rounded-xl px-3 text-sm font-semibold text-brand-teal underline underline-offset-2">
                {{ __('Atur pilihan') }}
            </button>
            <button type="button" x-show="settingsOpen" x-on:click="saveSettings()" data-test="cookie-save" class="min-h-11 rounded-xl border border-brand-teal px-4 text-sm font-semibold text-brand-teal">
                {{ __('Simpan pilihan') }}
            </button>
        </div>
    </div>
</section>

<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth.auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-feedback.auth-session-status class="text-center" :status="session('status')" />

        <x-auth.passkey-verify />

        <x-auth.google-button />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-forms.input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <x-forms.input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <x-actions.link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </x-actions.link>
                @endif
            </div>

            <!-- Remember Me -->
            <label class="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    name="remember"
                    @checked(old('remember'))
                    class="size-4 rounded border-zinc-300 accent-zinc-800 dark:border-zinc-600"
                />
                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Remember me') }}</span>
            </label>

            <div class="flex items-center justify-end">
                <x-actions.button type="submit" class="w-full" loading="auth.submit" data-test="login-button">
                    {{ __('Log in') }}
                </x-actions.button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <x-actions.link :href="route('register')" wire:navigate>{{ __('Sign up') }}</x-actions.link>
        </div>
    </div>
</x-layouts::auth>

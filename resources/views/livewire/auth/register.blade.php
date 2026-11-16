{{-- Fortify registration screen. After registering, mentees are sent to the profiling wizard (RegisterResponse). --}}
<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth.auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-feedback.auth-session-status class="text-center" :status="session('status')" />

        <x-auth.google-button />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <x-forms.input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <x-forms.input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <x-forms.input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <x-forms.input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Legal consent: both statements are validated again in CreateNewUser and logged in user_consents. -->
            <div class="flex flex-col gap-3">
                <div>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="terms" value="1" required @checked(old('terms')) class="mt-0.5 size-4 shrink-0 rounded border-zinc-300 accent-zinc-800" data-test="register-terms" />
                        <span class="text-zinc-700">
                            {{ __('Aku setuju dengan') }}
                            <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-semibold text-brand-teal underline underline-offset-2">{{ __('Syarat & Ketentuan') }}</a>
                            {{ __('dan') }}
                            <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="font-semibold text-brand-teal underline underline-offset-2">{{ __('Kebijakan Privasi') }}</a>
                        </span>
                    </label>
                    @error('terms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="age_confirmation" value="1" required @checked(old('age_confirmation')) class="mt-0.5 size-4 shrink-0 rounded border-zinc-300 accent-zinc-800" data-test="register-age" />
                        <span class="text-zinc-700">{{ __('Aku 18 tahun ke atas, atau orang tua/waliku sudah mengizinkan aku memakai Skular.') }}</span>
                    </label>
                    @error('age_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end">
                <x-actions.button type="submit" class="w-full" loading="auth.submit" data-test="register-user-button">
                    {{ __('Create account') }}
                </x-actions.button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <x-actions.link :href="route('login')" wire:navigate>{{ __('Log in') }}</x-actions.link>
        </div>
    </div>
</x-layouts::auth>

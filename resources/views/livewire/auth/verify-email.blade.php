<x-layouts::auth :title="__('Email verification')">
    <div class="mt-4 flex flex-col gap-6">
        <x-display.text class="text-center">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </x-display.text>

        @if (session('status') == 'verification-link-sent')
            <x-display.text class="text-center font-medium text-green-600 dark:text-green-400">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </x-display.text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-actions.button type="submit" class="w-full">
                    {{ __('Resend verification email') }}
                </x-actions.button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-actions.button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Log out') }}
                </x-actions.button>
            </form>
        </div>
    </div>
</x-layouts::auth>

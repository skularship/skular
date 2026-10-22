<section class="w-full">
    @include('partials.settings-heading')

    <x-display.heading level="2" class="sr-only">{{ __('Security settings') }}</x-display.heading>

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <x-forms.input
                wire:model="current_password"
                :label="__('Current password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <x-forms.input
                wire:model="password"
                :label="__('New password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            <x-forms.input
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center gap-4">
                <x-actions.button type="submit" data-test="update-password-button">{{ __('Save') }}</x-actions.button>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <x-display.heading>{{ __('Two-factor authentication') }}</x-display.heading>
                <x-display.subheading>{{ __('Manage your two-factor authentication settings') }}</x-display.subheading>

                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <x-display.text>
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </x-display.text>

                            <div class="flex justify-start">
                                <x-actions.button variant="danger" wire:click="disable">
                                    {{ __('Disable 2FA') }}
                                </x-actions.button>
                            </div>

                            <livewire:settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <x-display.text variant="subtle">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </x-display.text>

                            <x-actions.button variant="primary" wire:click="enable">
                                {{ __('Enable 2FA') }}
                            </x-actions.button>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if ($canManageTwoFactor)
            <x-overlay.modal
                name="two-factor-setup-modal"
                class="max-w-md md:min-w-md"
                @close="$wire.call('closeModal')"
                model="showModal"
            >
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="p-0.5 w-auto rounded-full border border-stone-100 dark:border-stone-600 bg-white dark:bg-stone-800 shadow-sm">
                            <div class="p-2.5 rounded-full border border-stone-200 dark:border-stone-600 overflow-hidden bg-stone-100 dark:bg-stone-200 relative">
                                <div class="flex items-stretch absolute inset-0 w-full h-full divide-x [&>div]:flex-1 divide-stone-200 dark:divide-stone-300 justify-around opacity-50">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <div></div>
                                    @endfor
                                </div>

                                <div class="flex flex-col items-stretch absolute w-full h-full divide-y [&>div]:flex-1 inset-0 divide-stone-200 dark:divide-stone-300 justify-around opacity-50">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <div></div>
                                    @endfor
                                </div>

                                <x-icons.app-icon name="qr-code" class="relative z-20 size-6 dark:text-accent-foreground" />
                            </div>
                        </div>

                        <div class="space-y-2 text-center">
                            <x-display.heading size="lg">{{ $this->modalConfig['title'] }}</x-display.heading>
                            <x-display.text>{{ $this->modalConfig['description'] }}</x-display.text>
                        </div>
                    </div>

                    @if ($showVerificationStep)
                        <div class="space-y-6">
                            <div
                                class="flex flex-col items-center space-y-3 justify-center"
                                x-data
                                x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                            >
                                <x-forms.otp
                                    model="code"
                                    :length="6"
                                    label="{{ __('OTP Code') }}"
                                    class="mx-auto"
                                />
                            </div>

                            <div class="flex items-center space-x-3">
                                <x-actions.button
                                    variant="outline"
                                    class="flex-1"
                                    wire:click="resetVerification"
                                >
                                    {{ __('Back') }}
                                </x-actions.button>

                                <x-actions.button
                                    variant="primary"
                                    class="flex-1"
                                    wire:click="confirmTwoFactor"
                                    x-bind:disabled="$wire.code.length < 6"
                                >
                                    {{ __('Confirm') }}
                                </x-actions.button>
                            </div>
                        </div>
                    @else
                        @error('setupData')
                            <x-feedback.callout variant="danger" icon="x-circle" :heading="$message" />
                        @enderror

                        <div class="flex justify-center">
                            <div class="relative aspect-square w-full max-w-64 overflow-hidden rounded-lg border border-stone-200 dark:border-stone-700">
                                @empty($qrCodeSvg)
                                    <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-stone-700 animate-pulse">
                                        <x-icons.app-icon name="spinner" class="size-6" />
                                    </div>
                                @else
                                <div x-data class="flex items-center justify-center h-full p-4">
                                    <div
                                        class="bg-white p-3 rounded"
                                        :style="$store.appearance.dark ? 'filter: invert(1) brightness(1.5)' : ''"
                                    >
                                            {!! $qrCodeSvg !!}
                                        </div>
                                    </div>
                                @endempty
                            </div>
                        </div>

                        <div>
                            <x-actions.button
                                :disabled="$errors->has('setupData')"
                                variant="primary"
                                class="w-full"
                                wire:click="showVerificationIfNecessary"
                            >
                                {{ $this->modalConfig['buttonText'] }}
                            </x-actions.button>
                        </div>

                        <div class="space-y-4">
                            <div class="relative flex items-center justify-center w-full">
                                <div class="absolute inset-0 w-full h-px top-1/2 bg-stone-200 dark:bg-stone-600"></div>
                                <span class="relative px-2 text-sm bg-white dark:bg-stone-800 text-stone-600 dark:text-stone-400">
                                    {{ __('or, enter the code manually') }}
                                </span>
                            </div>

                            <div
                                class="flex items-center space-x-2"
                                x-data="{
                                    copied: false,
                                    async copy() {
                                        try {
                                            await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 1500);
                                        } catch (e) {
                                            console.warn('Could not copy to clipboard');
                                        }
                                    }
                                }"
                            >
                                <div class="flex items-stretch w-full border rounded-xl dark:border-stone-700">
                                    @empty($manualSetupKey)
                                        <div class="flex items-center justify-center w-full p-3 bg-stone-100 dark:bg-stone-700">
                                            <x-icons.app-icon name="spinner" class="size-5" />
                                        </div>
                                    @else
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ $manualSetupKey }}"
                                            class="w-full p-3 bg-transparent outline-none text-stone-900 dark:text-stone-100"
                                        />

                                        <button
                                            x-on:click="copy()"
                                            class="px-3 transition-colors border-l cursor-pointer border-stone-200 dark:border-stone-600"
                                        >
                                            <x-icons.app-icon name="document-duplicate" class="size-5" x-show="!copied" />
                                            <x-icons.app-icon
                                                name="check"
                                                variant="s"
                                                class="size-5 text-green-500"
                                                x-show="copied"
                                                x-cloak
                                            />
                                        </button>
                                    @endempty
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </x-overlay.modal>
        @endif

        @if ($canManagePasskeys)
            <section class="mt-12">
                <x-display.heading>{{ __('Passkeys') }}</x-display.heading>
                <x-display.subheading>{{ __('Manage your passkeys for passwordless sign-in') }}</x-display.subheading>

                <div class="mt-6 flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    <div class="border rounded-lg border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        @forelse ($passkeys as $passkey)
                            <div class="flex flex-wrap items-center justify-between gap-3 p-4 {{ ! $loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                                <div class="flex min-w-0 items-center gap-4">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                        <x-icons.app-icon name="key" class="size-5 text-zinc-500 dark:text-zinc-400" />
                                    </div>
                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2.5">
                                            <p class="break-words font-medium tracking-tight">{{ $passkey['name'] }}</p>
                                            @if ($passkey['authenticator'])
                                                <x-feedback.badge>{{ $passkey['authenticator'] }}</x-feedback.badge>
                                            @endif
                                        </div>
                                        <p class="text-zinc-500 dark:text-zinc-400 text-xs">
                                            {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                            @if ($passkey['last_used_at_diff'])
                                                <span class="opacity-50 mx-1">/</span>
                                                {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <x-actions.button
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $passkey['id'] }})"
                                    class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
                                    aria-label="{{ __('Delete passkey') }}"
                                />
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                    <x-icons.app-icon name="key" class="size-7 text-zinc-400 dark:text-zinc-500" />
                                </div>
                                <p class="font-medium">{{ __('No passkeys yet') }}</p>
                                <x-display.text class="mt-1">{{ __('Add a passkey to sign in without a password') }}</x-display.text>
                            </div>
                        @endforelse
                    </div>

                    <x-auth.passkey-registration />
                </div>
            </section>
        @endif
    </x-settings.layout>

    <x-overlay.modal
        name="delete-passkey-modal"
        class="max-w-md md:min-w-md"
        @close="$wire.call('closeDeleteModal')"
        model="showDeleteModal"
    >
        <div class="space-y-6">
            <div class="space-y-2">
                <x-display.heading size="lg">{{ __('Remove passkey') }}</x-display.heading>
                <x-display.text>
                    {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
                </x-display.text>
            </div>

            <div class="flex gap-3 justify-end">
                <x-actions.button variant="outline" x-on:click="close()">
                    {{ __('Cancel') }}
                </x-actions.button>
                <x-actions.button variant="danger" wire:click="deletePasskey">
                    {{ __('Remove passkey') }}
                </x-actions.button>
            </div>
        </div>
    </x-overlay.modal>
</section>

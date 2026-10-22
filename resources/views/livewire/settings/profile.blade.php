<section class="w-full">
    @include('partials.settings-heading')

    <x-display.heading level="2" class="sr-only">{{ __('Profile settings') }}</x-display.heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <x-forms.input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <x-forms.input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <x-display.text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <x-actions.link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </x-actions.link>
                        </x-display.text>

                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <x-actions.button type="submit">{{ __('Save') }}</x-actions.button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>

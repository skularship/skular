<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <x-display.heading>{{ __('Delete account') }}</x-display.heading>
        <x-display.subheading>{{ __('Delete your account and all of its resources') }}</x-display.subheading>
    </div>

    <x-actions.button variant="danger" x-on:click="$dispatch('open-modal', 'confirm-user-deletion')">
        {{ __('Delete account') }}
    </x-actions.button>

    <x-overlay.modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <x-display.heading size="lg">{{ __('Are you sure you want to delete your account?') }}</x-display.heading>

                <x-display.subheading>
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </x-display.subheading>
            </div>

            <x-forms.input wire:model="password" :label="__('Password')" type="password" viewable />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <x-actions.button variant="filled" x-on:click="close()">{{ __('Cancel') }}</x-actions.button>

                <x-actions.button variant="danger" type="submit">{{ __('Delete account') }}</x-actions.button>
            </div>
        </form>
    </x-overlay.modal>
</section>

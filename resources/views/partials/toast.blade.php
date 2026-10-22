<div
    x-data="{
        toasts: [],
        counter: 0,
        addToast(detail) {
            const toast = {
                id: ++this.counter,
                variant: detail.variant || 'info',
                text: detail.text || detail.message || '',
            };

            this.toasts.push(toast);

            setTimeout(() => this.removeToast(toast.id), 5000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    }"
    x-on:toast.window="addToast(event.detail || {})"
    class="pointer-events-none fixed inset-x-0 z-[100] flex flex-col items-center gap-2 p-4 sm:inset-x-auto sm:end-6 sm:items-end {{ ($above_mobile_nav ?? false) ? 'bottom-20 lg:bottom-0' : 'bottom-0' }}"
    role="status"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="pointer-events-auto w-full max-w-sm rounded-xl border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-600 dark:bg-zinc-700"
        >
            <div class="flex items-start gap-3">
                <x-icons.app-icon name="information-circle" class="mt-0.5 hidden size-5 shrink-0 text-cyan-500 dark:text-cyan-400" x-show="! ['success', 'warning', 'danger', 'error'].includes(toast.variant)" />
                <x-icons.app-icon name="check-circle" class="mt-0.5 hidden size-5 shrink-0 text-lime-600 dark:text-lime-400" x-show="toast.variant === 'success'" />
                <x-icons.app-icon name="exclamation-triangle" class="mt-0.5 hidden size-5 shrink-0 text-amber-500 dark:text-amber-400" x-show="toast.variant === 'warning'" />
                <x-icons.app-icon name="x-circle" class="mt-0.5 hidden size-5 shrink-0 text-rose-500 dark:text-rose-400" x-show="toast.variant === 'danger' || toast.variant === 'error'" />

                <div class="flex-1 text-sm font-medium text-zinc-800 dark:text-white" x-text="toast.text"></div>

                <button
                    type="button"
                    x-on:click="removeToast(toast.id)"
                    aria-label="{{ __('Dismiss') }}"
                    class="-me-1 rounded-md p-1 text-zinc-400 transition hover:text-zinc-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent dark:hover:text-white"
                >
                    <x-icons.app-icon name="x-mark" class="size-4" />
                </button>
            </div>
        </div>
    </template>
</div>

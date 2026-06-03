@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('Sign in with a passkey'),
    'loadingLabel' => __('Authenticating...'),
    'separator' => __('Or continue with email'),
    'passkey' => true,
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = {{ $passkey ? 'true' : 'false' }} && Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ $passkey ? route($optionsRoute) : '' }}',
                        submit: '{{ $passkey ? route($submitRoute) : '' }}',
                    },
                });
                Livewire.navigate(response.redirect || '/dashboard');
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    {{-- Passkey supported: show passkey + optional social side by side --}}
    <template x-if="supported">
        <div>
            <div class="{{ isset($social) ? 'grid grid-cols-2 gap-3' : 'grid gap-2' }}">
                <flux:button
                    variant="outline"
                    icon="finger-print"
                    class="w-full"
                    x-on:click="verify()"
                    x-bind:disabled="loading"
                >
                    <span x-show="!loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                </flux:button>

                @isset($social)
                    {{ $social }}
                @endisset
            </div>

            <p x-show="error" x-text="error" x-cloak
               class="mt-2 text-sm text-center text-red-600 dark:text-red-400"></p>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="px-2 text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900">
                        {{ $separator }}
                    </span>
                </div>
            </div>
        </div>
    </template>

    {{-- Passkey not supported (or disabled) but social button provided: show it full-width --}}
    @isset($social)
        <template x-if="!supported">
            <div>
                {{ $social }}

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="px-2 text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900">
                            {{ $separator }}
                        </span>
                    </div>
                </div>
            </div>
        </template>
    @endisset
</div>

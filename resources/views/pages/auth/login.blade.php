<x-layouts::auth title="Log In">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Welcome Back!')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />


        <div class="grid grid-cols-2 gap-3">
            <a href="#">
                <flux:button class="w-full">
                    <x-slot name="icon">
                        <svg width="25" height="24" viewBox="0 0 25 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg" class="shrink-0">
                            <path
                                d="M23.06 12.25C23.06 11.47 22.99 10.72 22.86 10H12.5V14.26H18.42C18.16 15.63 17.38 16.79 16.21 17.57V20.34H19.78C21.86 18.42 23.06 15.6 23.06 12.25Z"
                                fill="#4285F4" />
                            <path
                                d="M12.4997 23C15.4697 23 17.9597 22.02 19.7797 20.34L16.2097 17.57C15.2297 18.23 13.9797 18.63 12.4997 18.63C9.63969 18.63 7.20969 16.7 6.33969 14.1H2.67969V16.94C4.48969 20.53 8.19969 23 12.4997 23Z"
                                fill="#34A853" />
                            <path
                                d="M6.34 14.0899C6.12 13.4299 5.99 12.7299 5.99 11.9999C5.99 11.2699 6.12 10.5699 6.34 9.90995V7.06995H2.68C1.93 8.54995 1.5 10.2199 1.5 11.9999C1.5 13.7799 1.93 15.4499 2.68 16.9299L5.53 14.7099L6.34 14.0899Z"
                                fill="#FBBC05" />
                            <path
                                d="M12.4997 5.38C14.1197 5.38 15.5597 5.94 16.7097 7.02L19.8597 3.87C17.9497 2.09 15.4697 1 12.4997 1C8.19969 1 4.48969 3.47 2.67969 7.07L6.33969 9.91C7.20969 7.31 9.63969 5.38 12.4997 5.38Z"
                                fill="#EA4335" />
                        </svg>
                    </x-slot>
                    Google
                </flux:button>
            </a>

            <a href="#">
                <flux:button class="w-full">
                    <x-slot name="icon">
                        <svg width="25" height="24" class="text-[#1877F2]" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </x-slot>
                    Facebook
                </flux:button>
            </a>
        </div>

        <flux:separator text="or continue with email" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6 mt-4">
            @csrf

            <!-- Email Address -->
            <flux:input name="email" :label="__('Email address')" :value="old('email')" type="email" required
                autofocus autocomplete="email" placeholder="email@example.com" />

            <!-- Password -->
            <div class="relative">
                <flux:input name="password" :label="__('Password')" type="password" required
                    autocomplete="current-password" :placeholder="__('Password')" viewable />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>

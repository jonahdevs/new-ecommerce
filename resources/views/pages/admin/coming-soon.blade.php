<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Coming Soon')] class extends Component {
    //
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Coming Soon</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-center min-h-[60vh] px-4 py-16">
        <div class="text-center max-w-lg w-full">

            {{-- Icon --}}
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-50 dark:bg-blue-950/50 mb-6">
                <flux:icon.rocket-launch class="w-10 h-10 text-blue-600 dark:text-blue-400" />
            </div>

            {{-- Divider --}}
            <div class="w-12 h-1 rounded-full bg-blue-600 dark:bg-blue-400 mx-auto mb-5"></div>

            {{-- Title --}}
            <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-100 mb-3">
                Coming Soon
            </h1>

            {{-- Message --}}
            <p class="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed mb-8">
                This feature is currently under development and will be available in an upcoming release.
            </p>

            {{-- Features List --}}
            <div class="mb-8 p-6 bg-zinc-50 dark:bg-zinc-900 rounded-lg text-left">
                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-4">
                    What to expect
                </p>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400 shrink-0 mt-0.5" />
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Intuitive and user-friendly interface</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400 shrink-0 mt-0.5" />
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Powerful features to streamline your workflow</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400 shrink-0 mt-0.5" />
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Seamless integration with existing tools</p>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-secondary hover:bg-secondary-hover text-white text-sm font-medium rounded-md transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            {{-- Footer Note --}}
            <div class="mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Have feedback or suggestions? Contact your system administrator.
                </p>
            </div>

        </div>
    </div>
</div>

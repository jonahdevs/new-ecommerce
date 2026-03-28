@props(['enabled', 'environment' => 'sandbox'])

@if ($enabled)
    @if ($environment === 'live')
        <span
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
            {{ __('Live') }}
        </span>
    @else
        <span
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            {{ __('Sandbox') }}
        </span>
    @endif
@else
    <span
        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
        {{ __('Disabled') }}
    </span>
@endif

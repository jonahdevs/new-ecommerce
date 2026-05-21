@props(['model', 'title', 'description', 'last' => false])

<div @class([
    'flex items-center justify-between gap-4 px-5 py-3.5 border-b border-zinc-200 dark:border-zinc-700',
    'border-b-0' => $last,
])>
    <div class="flex-1">
        <div class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-100 mb-0.5">{{ $title }}</div>
        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $description }}</div>
    </div>

    <div class="flex items-center gap-5 shrink-0">
        {{-- Email --}}
        <label class="relative inline-block w-9 h-5 cursor-pointer">
            <input type="checkbox" class="peer sr-only" wire:model.live="{{ $model }}">
            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full peer-checked:bg-primary transition-colors"></div>
            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
        </label>

        {{-- SMS (disabled) --}}
        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
            <input type="checkbox" class="peer sr-only" disabled>
            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
        </label>

        {{-- Push (disabled) --}}
        <label class="relative inline-block w-9 h-5 cursor-not-allowed opacity-40">
            <input type="checkbox" class="peer sr-only" disabled>
            <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full transition-colors"></div>
            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
        </label>
    </div>
</div>

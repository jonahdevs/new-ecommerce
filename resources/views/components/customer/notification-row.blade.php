@props([
    'title',
    'description',
    'topic',
    'channels' => ['email', 'sms', 'push'],
    'lastItem' => false,
])

<div @class(['flex items-center justify-between gap-4 px-5 py-3.5 border-b border-zinc-200', 'border-b-0' => $lastItem])>
    <div class="flex-1">
        <div class="text-[13px] font-semibold text-zinc-950 mb-0.5">{{ $title }}</div>
        <div class="text-[11px] text-zinc-500 leading-relaxed">{{ $description }}</div>
    </div>
    <div class="flex items-center gap-5 shrink-0">
        @foreach ($channels as $channel)
            <label class="relative inline-block w-9 h-5 cursor-pointer">
                <input type="checkbox" class="peer sr-only" wire:model.live="prefs.{{ $topic }}.{{ $channel }}">
                <div class="w-9 h-5 bg-zinc-200 rounded-full peer-checked:bg-brand-primary transition-colors"></div>
                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
            </label>
        @endforeach
    </div>
</div>

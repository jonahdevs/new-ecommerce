@props(['title', 'description', 'lastItem' => false])

<div @class(['flex items-start justify-between gap-5 px-5 py-4 border-b border-zinc-200', 'border-b-0' => $lastItem])>
    <div class="flex-1">
        <div class="text-[13px] font-bold text-zinc-950 mb-0.5">{{ $title }}</div>
        <div class="text-[12px] text-zinc-500 leading-relaxed">{{ $description }}</div>
    </div>
    <div class="flex items-center gap-2.5 shrink-0">
        {{ $slot }}
    </div>
</div>

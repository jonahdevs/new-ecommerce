@props([
    'rating' => 0,
    'count' => null,
    'size' => 'sm', // sm | md
    'showCount' => false,
])

@php
    $avgRating = (float) $rating;
    $iconClass = $size === 'md' ? 'w-5 h-5' : 'w-4 h-4';
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-1']) }}>
    <div class="flex items-center gap-0.5">
        @for ($i = 0; $i < 5; $i++)
            @if ($avgRating >= $i + 1)
                <flux:icon.star variant="solid" class="{{ $iconClass }} text-yellow-400" />
            @elseif ($avgRating > $i)
                <div class="relative {{ $iconClass }}">
                    <flux:icon.star variant="solid" class="{{ $iconClass }} text-zinc-300" />
                    <div class="absolute inset-0 overflow-hidden w-1/2">
                        <flux:icon.star variant="solid" class="{{ $iconClass }} text-yellow-400" />
                    </div>
                </div>
            @else
                <flux:icon.star variant="solid" class="{{ $iconClass }} text-zinc-300" />
            @endif
        @endfor
    </div>

    @if ($avgRating > 0)
        <span class="text-xs text-zinc-500">{{ number_format($avgRating, 1) }}</span>
    @endif

    @if ($showCount && $count !== null)
        <span class="text-xs text-zinc-400">({{ $count }})</span>
    @endif
</div>

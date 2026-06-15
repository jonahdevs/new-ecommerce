@props([
    'label',
    'value',
    'icon' => null,
    'hint' => null,
    'tone' => 'zinc',
    'trend' => null,
    'trendLabel' => 'vs last period',
])

@php
    $tones = [
        'zinc' => ['icon' => 'text-zinc-400', 'bg' => 'bg-zinc-100 dark:bg-zinc-800'],
        'emerald' => ['icon' => 'text-emerald-600', 'bg' => 'bg-emerald-50 dark:bg-emerald-950/50'],
        'blue' => ['icon' => 'text-blue-600', 'bg' => 'bg-blue-50 dark:bg-blue-950/50'],
        'violet' => ['icon' => 'text-violet-600', 'bg' => 'bg-violet-50 dark:bg-violet-950/50'],
        'teal' => ['icon' => 'text-teal-600', 'bg' => 'bg-teal-50 dark:bg-teal-950/50'],
        'amber' => ['icon' => 'text-amber-600', 'bg' => 'bg-amber-50 dark:bg-amber-950/50'],
    ];
    $t = $tones[$tone] ?? $tones['zinc'];
    $hasTrend = $trend !== null;
    $trendUp = $hasTrend && $trend >= 0;
@endphp

<flux:card class="p-5">
    <div class="flex items-start justify-between gap-3">
        <div class="text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $label }}</div>
        @if ($icon)
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $t['bg'] }}">
                <flux:icon :name="$icon" class="size-4 {{ $t['icon'] }}" />
            </div>
        @endif
    </div>

    <div class="mt-3 text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">{!! $value !!}</div>

    <div class="mt-1.5 flex items-center gap-1.5">
        @if ($hasTrend)
            <span @class([
                'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold',
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' => $trendUp,
                'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400' => ! $trendUp,
            ])>
                {{ $trendUp ? '▲' : '▼' }} {{ abs($trend) }}%
            </span>
        @endif
        @if ($hint)
            <span class="text-xs text-zinc-400">{{ $hint }}</span>
        @elseif ($hasTrend)
            <span class="text-xs text-zinc-400">{{ $trendLabel }}</span>
        @endif
    </div>
</flux:card>

@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'hint' => null,
])

@php
    $hasPrefix = isset($prefix);
    $hasAppend = isset($append);
    $hasSuffix = isset($suffix);
@endphp

<div>
    @if ($label)
        <label class="block text-[10px] font-bold tracking-widest uppercase text-zinc-500 mb-1.5">{{ $label }}@if ($required) *@endif</label>
    @endif

    @if ($hasPrefix || $hasAppend)
        <div class="flex">
            @if ($hasPrefix)
                <span class="flex items-center px-3 border-y-[1.5px] border-l-[1.5px] border-zinc-200 bg-zinc-50 text-[13px] font-bold text-zinc-500 shrink-0">{{ $prefix }}</span>
            @endif
            {{ $slot }}
            @if ($hasAppend)
                {{ $append }}
            @endif
        </div>
    @elseif ($hasSuffix)
        <div class="relative">
            {{ $slot }}
            {{ $suffix }}
        </div>
    @else
        {{ $slot }}
    @endif

    @if ($hint)
        <div class="text-[11px] text-zinc-500 mt-1">{{ $hint }}</div>
    @endif

    @if ($name)
        @error($name)
            <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
        @enderror
    @endif
</div>

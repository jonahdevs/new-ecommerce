@props([
    'title' => '',
    'titleEm' => '',
    'bodyClass' => 'p-5',
])

<div {{ $attributes->merge(['class' => 'bg-white border border-zinc-200']) }}>
    @if ($title || isset($icon) || isset($action))
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-200">
            <div
                class="flex items-center gap-2 font-serif text-base font-bold uppercase tracking-[0.04em] [&_svg]:w-3.75 [&_svg]:h-3.75 [&_svg]:text-brand-primary">
                {{ $icon ?? '' }}
                <span>{{ $title }}@if ($titleEm)
                        <em class="text-primary not-italic">{{ $titleEm }}</em>
                    @endif
                </span>
            </div>
            @if (isset($action))
                <div>{{ $action }}</div>
            @endif
        </div>
    @endif

    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>

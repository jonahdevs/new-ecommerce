@props(['title', 'titleEm' => '', 'danger' => false])

<div @class([
    'bg-white border',
    'border-red-500' => $danger,
    'border-zinc-200' => !$danger,
])>
    <div @class([
        'flex items-center justify-between px-5 py-4 border-b',
        'border-red-200 bg-red-50/40' => $danger,
        'border-zinc-200' => !$danger,
    ])>
        <div class="flex items-center gap-2">
            @if (isset($icon))
                <div @class([
                    '[&_svg]:w-[15px] [&_svg]:h-[15px]',
                    'text-red-500' => $danger,
                    'text-brand-primary' => !$danger,
                ])>
                    {{ $icon }}
                </div>
            @endif
            <h3 class="font-barlow-condensed text-[16px] font-extrabold uppercase tracking-wide">
                <span @class(['text-red-500' => $danger, 'text-zinc-950' => !$danger])>{{ $title }}</span>
                @if ($titleEm)
                    <em @class([
                        'not-italic',
                        'text-red-500' => $danger,
                        'text-brand-primary' => !$danger,
                    ])>{{ $titleEm }}</em>
                @endif
            </h3>
        </div>
        @if (isset($action))
            <div>{{ $action }}</div>
        @endif
    </div>

    <div>
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="flex items-center gap-2.5 px-5 py-4 border-t border-zinc-200">
            {{ $footer }}
        </div>
    @endif
</div>

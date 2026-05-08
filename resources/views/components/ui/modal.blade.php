@props([
    'title' => '',
    'maxWidth' => '560px',
])

<div
    {{ $attributes->merge(['class' => 'fixed inset-0 bg-black/60 backdrop-blur-sm z-[700] flex items-start justify-center p-4 pt-16 overflow-y-auto']) }}>
    <div class="bg-white w-full shadow-2xl animate-[modalSlideUp_0.25s_ease]" style="max-width: {{ $maxWidth }}"
        wire:click.stop>
        {{-- Header --}}
        @isset($header)
            {{ $header }}
        @else
            <div class="flex items-center justify-between px-6 py-5 bg-white border-b">
                <h2 class="font-serif text-lg font-extrabold uppercase tracking-tight text-zinc-950">
                    {!! $title !!}
                </h2>

                @isset($close)
                    {{ $close }}
                @endisset
            </div>
        @endisset

        {{-- Body --}}
        {{ $slot }}

        {{-- Footer --}}
        @isset($footer)
            {{ $footer }}
        @endisset
    </div>
</div>

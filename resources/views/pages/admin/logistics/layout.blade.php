@props([
    'heading' => '',
    'subheading' => '',
    'actions' => null,
])

<div>
    @if ($heading || $subheading)
        <div class="flex items-start justify-between mb-5">
            <div>
                @if ($heading)
                    <flux:heading size="xl">{{ $heading }}</flux:heading>
                @endif
                @if ($subheading)
                    <flux:subheading>{{ $subheading }}</flux:subheading>
                @endif
            </div>
            @if (isset($actions) && !$actions->isEmpty())
                <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>

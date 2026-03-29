@props([
    'label',
    'model', // wire:model binding string e.g. "form.consumer_key"
    'hasValue', // bool — whether an encrypted value is already stored
    'placeholder' => '',
    'description' => '',
])

<flux:field>
    <flux:label>{{ $label }}</flux:label>

    @if ($hasValue)
        {{-- Value already set — show masked indicator and allow override --}}
        <div class="flex items-center gap-3">
            <div
                class="flex-1 flex items-center gap-2 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800">
                <flux:icon.lock-closed class="w-4 h-4 text-zinc-400 shrink-0" />
                <span class="text-sm text-zinc-400 tracking-widest">••••••••••••</span>
            </div>
            <flux:button size="sm" x-data
                x-on:click="$wire.set('{{ $model }}', ''); $nextTick(() => $el.closest('[data-field]')?.querySelector('input')?.focus())"
                class="cursor-pointer shrink-0">
                {{ __('Change') }}
            </flux:button>
        </div>

        {{-- Hidden input so validation still works --}}
        <input type="hidden" wire:model="{{ $model }}" />
    @else
        {{-- No value set — show normal password input --}}
        <flux:input type="password" wire:model="{{ $model }}"
            placeholder="{{ $placeholder ?: __('Enter value') }}" autocomplete="new-password" />
    @endif

    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    <flux:error name="{{ $model }}" />
</flux:field>

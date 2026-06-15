{{-- "Complete your purchase" accessory prompt. Rendered inside any Livewire
     page that uses the InteractsWithStorefront trait; opens after a product
     with accessory links is added to the cart. --}}
<flux:modal wire:model.self="showAccessoryModal" class="md:w-[560px]">
    <flux:heading>Complete your purchase</flux:heading>
    <flux:subheading>
        @if ($accessoryParentName !== '')
            {{ $accessoryParentName }} works best with these. Adjust the quantities or uncheck anything you don't need.
        @else
            These accessories pair with your item.
        @endif
    </flux:subheading>

    <div class="mt-5 divide-y divide-zinc-100">
        @foreach ($accessoryModalItems as $item)
            @php $sel = $accessorySelections[$item['slug']] ?? ['checked' => false, 'qty' => 1]; @endphp
            <div class="flex items-center gap-3 py-3" wire:key="acc-{{ $item['slug'] }}">
                <flux:checkbox wire:model.live="accessorySelections.{{ $item['slug'] }}.checked" />

                @if ($item['image'])
                    <img src="{{ $item['image'] }}" alt="" class="size-12 shrink-0 object-contain" loading="lazy" />
                @else
                    <div class="grid size-12 shrink-0 place-items-center overflow-hidden rounded border border-zinc-100 bg-surface-sunken text-ink-4">
                        <flux:icon.cube variant="micro" class="size-4" />
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <div class="truncate text-[13px] font-semibold text-ink">{{ $item['name'] }}</div>
                    <div class="mt-0.5 flex items-center gap-2 text-[12px] text-ink-3">
                        <span class="tabular-nums">{!! $item['price_cents'] ? money($item['price_cents']) : 'POA' !!}</span>
                        @if ($item['is_required'])
                            <flux:badge size="sm" color="amber" inset="top bottom">Recommended</flux:badge>
                        @endif
                        @unless ($item['in_stock'])
                            <flux:badge size="sm" color="zinc" inset="top bottom">Made to order</flux:badge>
                        @endunless
                    </div>
                </div>

                <div @class([
                    'inline-flex h-9 shrink-0 items-stretch overflow-hidden rounded border border-zinc-200 transition',
                    'opacity-40' => ! ($sel['checked'] ?? false),
                ])>
                    <button type="button" wire:click="decAccessoryQty('{{ $item['slug'] }}')" aria-label="Decrease quantity"
                        class="grid w-8 cursor-pointer place-items-center text-ink-2 transition hover:bg-surface-sunken">
                        <flux:icon.minus variant="micro" class="size-3.5" />
                    </button>
                    <div class="grid w-9 place-items-center border-x border-zinc-200 text-[13px] font-semibold tabular-nums">
                        {{ $sel['qty'] ?? 1 }}
                    </div>
                    <button type="button" wire:click="incAccessoryQty('{{ $item['slug'] }}')" aria-label="Increase quantity"
                        class="grid w-8 cursor-pointer place-items-center text-ink-2 transition hover:bg-surface-sunken">
                        <flux:icon.plus variant="micro" class="size-3.5" />
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-5 flex items-center justify-end gap-2 border-t border-zinc-200 pt-4">
        <flux:button variant="ghost" wire:click="closeAccessoryModal">No thanks</flux:button>
        <flux:button variant="primary" icon="shopping-cart" wire:click="addSelectedAccessories">Add selected</flux:button>
    </div>
</flux:modal>

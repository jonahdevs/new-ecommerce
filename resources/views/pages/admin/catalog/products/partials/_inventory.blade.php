{{-- Inventory --}}
<div wire:cloak wire:show="activeTab == 'inventory'" class="space-y-5 p-5">

    {{-- Virtual product notice --}}
    <div wire:show="form.is_virtual" wire:cloak>
        <flux:callout variant="warning" icon="information-circle">
            <flux:callout.heading>Virtual product</flux:callout.heading>
            <flux:callout.text>
                Stock management does not apply to virtual products.
                These fields will be ignored on save.
            </flux:callout.text>
        </flux:callout>
    </div>


    {{-- SKU — always visible --}}
    <flux:field>
        <flux:input label="SKU" wire:model="form.sku" placeholder="e.g. PROD-001" />
        @if ($form->type === 'grouped')
            <flux:description>Optional for grouped products — used as a kit reference number.</flux:description>
        @endif
        <flux:error name="form.sku" />
    </flux:field>


    {{-- Manage Stock --}}
    <flux:field>
        <flux:label>Manage Stock</flux:label>
        <flux:checkbox wire:model="form.manage_stock" label="Enable stock management for this product" />
    </flux:field>

    {{-- Stock fields — only when manage_stock is on --}}
    <div wire:cloak wire:show="form.manage_stock" class="space-y-5">
        <flux:input wire:model="form.stock_quantity" label="Stock Quantity" type="number" min="0" />

        <div class="grid grid-cols-2 gap-5">
            <flux:field>
                <flux:label>Allow Backorder?</flux:label>
                <select wire:model="form.allow_backorder"
                    class="w-full text-sm rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-zinc-400 dark:focus:ring-zinc-500">
                    <option value="no">Do not allow</option>
                    <option value="notify">Allow, but notify customer</option>
                    <option value="yes">Allow</option>
                </select>
            </flux:field>

            <flux:input wire:model="form.low_stock_threshold" label="Low Stock Threshold" type="number"
                min="0" />
        </div>
    </div>

    {{-- Stock Status — only when manage_stock is off --}}
    <div wire:cloak wire:show="!form.manage_stock">
        <flux:field>
            <flux:label>Stock Status</flux:label>
            <select wire:model="form.stock_status"
                class="w-full text-sm rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-zinc-400 dark:focus:ring-zinc-500">
                <option value="in_stock">In Stock</option>
                <option value="out_of_stock">Out of Stock</option>
                <option value="backorder">Backorder</option>
            </select>
        </flux:field>
    </div>

    <flux:separator />

    {{-- Sold Individually --}}
    <flux:field>
        <flux:label>Sold Individually</flux:label>
        <flux:checkbox wire:model="form.sold_individually"
            label="Only allow one of this item to be bought in a single order" />
    </flux:field>
</div>

<div class="space-y-4">
    <div class="text-sm text-zinc-500">
        Add descriptive pieces of information that customers can use to search for this product,
        such as "Material" or "Color".
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center gap-3">
        <flux:button type="button" wire:click="addNewAttribute" icon="plus">
            Add New
        </flux:button>

        <flux:select wire:change="addExistingAttribute($event.target.value)" class="max-w-fit">
            <flux:select.option value="">Add existing...</flux:select.option>
            @foreach ($this->productAttributes as $attr)
                <flux:select.option :value="$attr->id">
                    {{ ucfirst($attr->name) }}
                </flux:select.option>
            @endforeach
        </flux:select>

        @if (!empty($selectedAttributes))
            <div class="ms-auto flex items-center gap-2 text-sm text-zinc-500">
                {{ count($selectedAttributes) }} attribute(s)
            </div>
        @endif
    </div>

    {{-- Attribute Rows --}}
    @foreach ($selectedAttributes as $index => $attr)
        {{-- Single x-data wrapping BOTH header and body so open state is shared --}}
        <flux:card class="p-0" wire:key="attr-row-{{ $index }}" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">

            {{-- Header --}}
            <div class="flex items-center gap-4 px-4 py-2" :class="{ 'border-b dark:border-zinc-700': open }">

                <flux:heading size="sm">
                    {{ $attr['name'] ? ucfirst($attr['name']) : 'New Attribute' }}
                </flux:heading>

                <div class="ms-auto flex items-center gap-2">
                    @if (
                        !(count($selectedAttributes) === 1 &&
                            $attr['attribute_id'] === null &&
                            $attr['is_new'] === true &&
                            empty($attr['name']) &&
                            empty($attr['values'])
                        ))
                        <flux:button size="xs" icon="trash" icon-variant="outline" type="button" variant="ghost"
                            wire:click="removeSelectedAttribute({{ $index }})"
                            wire:confirm="Remove this attribute?" class="text-red-500!" />
                    @endif

                    <flux:button icon="chevron-down" size="xs" variant="ghost" type="button"
                        class="transition-transform duration-300" x-bind:class="{ 'rotate-180': open }"
                        @click="open = !open" />
                </div>
            </div>

            {{-- Body — shares x-data from parent flux:card --}}
            <div x-show="open" x-collapse class="grid grid-cols-3 gap-5 p-5">

                <div class="col-span-1 space-y-4">
                    @if ($attr['is_new'])
                        <flux:input label="Name" wire:model="selectedAttributes.{{ $index }}.name"
                            placeholder="e.g., Size, Material" />
                    @else
                        <flux:field>
                            <flux:label>Name</flux:label>
                            <p class="text-sm font-semibold">{{ ucfirst($attr['name']) }}</p>
                        </flux:field>
                    @endif

                    <flux:checkbox wire:model="selectedAttributes.{{ $index }}.is_visible"
                        label="Visible on the product page" />

                    <flux:checkbox wire:model="selectedAttributes.{{ $index }}.is_variation_attribute"
                        label="Used for variations" />
                </div>

                <div class="col-span-2">
                    @if ($attr['is_new'])
                        <flux:textarea label="Value(s)" wire:model="selectedAttributes.{{ $index }}.values"
                            placeholder="Enter values separated by '|' e.g. Blue | Large | Medium" rows="3" />
                    @else
                        <x-my-choices wire:model="selectedAttributes.{{ $index }}.values" :options="$this->getProductAttributeValues($attr['attribute_id'])"
                            placeholder="Search and select values..." clearable />
                    @endif
                </div>
            </div>
        </flux:card>
    @endforeach

    {{-- Save Attributes Button --}}
    @if (!empty($selectedAttributes))
        <div class="flex items-center justify-between pt-2 border-t dark:border-zinc-700">
            <p class="text-sm text-zinc-500">
                Changes will also be saved when you save the product.
            </p>
            <flux:button type="button" variant="primary" wire:click="saveAttributes" icon="check">
                Save Attributes
            </flux:button>
        </div>
    @endif
</div>

<div class="space-y-4" x-data="{
    allCollapsed: false,
    init() {
        this.$watch('allCollapsed', (value) => {
            this.$dispatch('toggle-all-attributes', { collapsed: value })
        })
    }
}">
    <div class="text-sm text-zinc-500">
        Add descriptive pieces of information that customers can use to search for this product,
        such as "Material" or "Color".
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center gap-3">
        <flux:button type="button" wire:click="addNewAttribute" icon="plus">
            Add New
        </flux:button>

        <flux:select wire:model.live="selectedExistingAttribute" placeholder="Add existing" class="max-w-fit">
            @foreach ($this->productAttributes as $attr)
                <flux:select.option :value="$attr->id">
                    {{ ucfirst($attr->name) }}
                </flux:select.option>
            @endforeach
        </flux:select>

        @if (!empty($selectedAttributes))
            <div class="ms-auto flex items-center gap-2 text-sm">
                <span>{{ count($selectedAttributes) }} attribute(s)</span>
                (
                <button type="button" @click="allCollapsed = true"
                    class="text-blue-500 italic cursor-pointer">Expand</button>
                /
                <button type="button" @click="allCollapsed = false"
                    class="text-blue-500 italic cursor-pointer">Close</button>
                )
            </div>
        @endif
    </div>

    {{-- Attribute Rows --}}
    @foreach ($selectedAttributes as $index => $attr)
        <div class="border p-5 rounded-sm" wire:key="attribute-{{ $index }}" x-data="{ collapsed: {{ $loop->first ? 'true' : 'false' }} }"
            @toggle-all-attributes.window="collapsed = $event.detail.collapsed">

            {{-- Header --}}
            <div class="flex items-center gap-4" :class="{ 'border-b pb-1 mb-4': collapsed }">
                <flux:heading size="lg">
                    {{ $attr['name'] ? ucfirst($attr['name']) : 'New Attribute' }}
                </flux:heading>

                <div class="ms-auto flex items-center gap-3 text-sm">
                    @if (
                        !(count($selectedAttributes) === 1 &&
                            $attr['attribute_id'] === null &&
                            $attr['is_new'] === true &&
                            empty($attr['name']) &&
                            empty($attr['values'])
                        ))
                        <button type="button" wire:click="removeSelectedAttribute({{ $index }})"
                            class="text-red-500 cursor-pointer">Remove</button>
                    @endif

                    <button type="button" @click="collapsed = !collapsed"
                        class="text-blue-500 cursor-pointer">Edit</button>
                </div>
            </div>

            {{-- Body --}}
            <div x-cloak x-show="collapsed" x-collapse class="grid grid-cols-3 gap-5">
                <div class="col-span-1 space-y-4">
                    @if ($attr['is_new'])
                        <flux:input label="Name" wire:model.blur="selectedAttributes.{{ $index }}.name"
                            placeholder="e.g., Size, Material" />
                    @else
                        <div class="text-sm">
                            Name: <span class="font-semibold ms-1">{{ ucfirst($attr['name']) }}</span>
                        </div>
                    @endif

                    <flux:checkbox wire:model.live="selectedAttributes.{{ $index }}.is_visible"
                        label="Visible on the product page" />

                    <flux:checkbox wire:model.live="selectedAttributes.{{ $index }}.is_variation_attribute"
                        label="Used for variations" />
                </div>

                <div class="col-span-2">
                    @if ($attr['is_new'])
                        <flux:textarea label="Value(s)" wire:model.blur="selectedAttributes.{{ $index }}.values"
                            placeholder="Enter values separated by '|' e.g. Blue | Large | Medium" />
                    @else
                        <x-my-choices wire:model.live="selectedAttributes.{{ $index }}.values" :options="$this->getProductAttributeValues($attr['attribute_id'])"
                            placeholder="Search values..." clearable />
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

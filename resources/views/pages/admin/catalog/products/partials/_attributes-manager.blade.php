<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;

new class extends Component {
    public ?int $productId = null;
    public array $selectedAttributes = [];
    public ?int $selectedExistingAttribute = null;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->productId = $product->id;
            $this->loadExistingAttributes($product);
        }
    }

    private function loadExistingAttributes(Product $product): void
    {
        $this->selectedAttributes = $product
            ->attributes()
            ->with('values')
            ->get()
            ->map(
                fn($attr) => [
                    'attribute_id' => $attr->id,
                    'name' => $attr->name,
                    'is_new' => false,
                    'is_visible' => $attr->pivot->is_visible,
                    'is_variation_attribute' => $attr->pivot->is_variation_attribute,
                    'sort_order' => $attr->pivot->sort_order,
                    'values' => json_decode($product->attributes()->where('attributes.id', $attr->id)->first()->pivot->values ?? '[]', true) ?? [],
                ],
            )
            ->toArray();
    }

    // -----------------------------------------------
    // Push state to parent on save
    // -----------------------------------------------

    #[On('push-state-to-parent')]
    public function pushState(): void
    {
        $this->dispatch('attributes-state-ready', attributes: $this->selectedAttributes);
    }

    // -----------------------------------------------
    // Add / Remove Attributes
    // -----------------------------------------------

    public function addNewAttribute(): void
    {
        $this->selectedAttributes[] = [
            'attribute_id' => null,
            'name' => '',
            'is_new' => true,
            'is_visible' => true,
            'is_variation_attribute' => false,
            'sort_order' => count($this->selectedAttributes),
            'values' => '',
        ];
    }

    public function updatedSelectedExistingAttribute($attributeId): void
    {
        if (!$attributeId) {
            return;
        }

        $already = collect($this->selectedAttributes)->pluck('attribute_id')->contains((int) $attributeId);

        if ($already) {
            $this->dispatch('notify', variant: 'warning', message: 'Attribute already added.');
            $this->selectedExistingAttribute = null;
            return;
        }

        $attribute = Attribute::find($attributeId);
        if (!$attribute) {
            return;
        }

        $this->selectedAttributes[] = [
            'attribute_id' => $attribute->id,
            'name' => $attribute->name,
            'is_new' => false,
            'is_visible' => true,
            'is_variation_attribute' => false,
            'sort_order' => count($this->selectedAttributes),
            'values' => [],
        ];

        $this->selectedExistingAttribute = null;
    }

    public function removeSelectedAttribute(int $index): void
    {
        array_splice($this->selectedAttributes, $index, 1);
        $this->selectedAttributes = array_values($this->selectedAttributes);
        $this->dispatchAttributesUpdated();
    }

    // -----------------------------------------------
    // Get values for existing attribute
    // -----------------------------------------------

    public function getProductAttributeValues(int $attributeId): array
    {
        return AttributeValue::where('attribute_id', $attributeId)->where('is_active', true)->orderBy('sort_order')->get()->map(fn($v) => ['id' => $v->id, 'name' => $v->label ?: $v->value])->toArray();
    }

    // -----------------------------------------------
    // Notify VariationsManager when variation attributes change
    // -----------------------------------------------

    public function dispatchAttributesUpdated(): void
    {
        $variationAttributes = collect($this->selectedAttributes)->filter(fn($a) => $a['is_variation_attribute'])->values()->toArray();

        $this->dispatch('attributes-updated', attributes: $variationAttributes);
    }

    // -----------------------------------------------
    // Computed
    // -----------------------------------------------

    #[Computed]
    public function productAttributes()
    {
        return Attribute::where('is_active', true)->orderBy('sort_order')->get();
    }
};
?>

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
        <flux:button type="button" wire:click="addNewAttribute" icon="plus" size="sm">
            Add New
        </flux:button>

        <flux:select wire:model.live="selectedExistingAttribute" placeholder="Add existing" class="max-w-fit"
            size="sm">
            @foreach ($this->productAttributes as $attr)
                <flux:select.option :value="$attr->id">{{ ucfirst($attr->name) }}</flux:select.option>
            @endforeach
        </flux:select>

        @if (!empty($selectedAttributes))
            <div class="ms-auto flex items-center gap-2 text-xs!">
                <span class="me-2">{{ count($selectedAttributes) }} attribute(s)</span>
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
        <flux:card class="p-0" wire:key="attribute-{{ $index }}" x-data="{ collapsed: {{ $loop->first ? 'true' : 'false' }} }"
            @toggle-all-attributes.window="collapsed = $event.detail.collapsed">

            {{-- Header --}}
            <div class="flex items-center gap-4 px-4 py-2" :class="{ 'border-b': collapsed }">
                <flux:heading>
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
                        <flux:button type="button" icon="trash" icon-variant="outline" size="xs" variant="ghost"
                            class="cursor-pointer text-red-500!" tooltip="Delete Attribute"
                            wire:click="removeSelectedAttribute({{ $index }})" />
                    @endif

                    <flux:button icon="chevron-down" size="xs" variant="ghost"
                        class="cursor-pointer transition-transform duration-300"
                        x-bind:class="{ 'rotate-180': collapsed }" @click="collapsed = !collapsed" />
                </div>
            </div>

            {{-- Body --}}
            <div x-cloak x-show="collapsed" x-collapse class="grid grid-cols-3 gap-5 p-5">
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
                        label="Used for variations" wire:change="dispatchAttributesUpdated" />
                </div>

                <div class="col-span-2">
                    @if ($attr['is_new'])
                        <flux:textarea label="Value(s)" wire:model.blur="selectedAttributes.{{ $index }}.values"
                            placeholder="Enter values separated by '|' e.g. Blue | Large | Medium" />
                    @else
                        <x-my-choices wire:model.live="selectedAttributes.{{ $index }}.values" :options="$this->getProductAttributeValues($attr['attribute_id'])"
                            placeholder="Search values..." clearable wire:change="dispatchAttributesUpdated" />
                    @endif
                </div>
            </div>
        </flux:card>
    @endforeach
</div>

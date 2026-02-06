<?php

use App\Models\AttributeValue;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

new class extends Component {
    use WithFileUploads;

    public $attributeId;
    public $valueId = null;

    // Fields
    public $value = '';
    public $label = '';
    public $slug = '';
    public $color_code = '';
    public $image_path;
    public $is_active = true;
    public $sort_order = 0;

    #[On('create-value')]
    public function createValue($attributeId)
    {
        $this->reset(['valueId', 'value', 'label', 'slug', 'color_code', 'image_path', 'is_active', 'sort_order']);
        $this->attributeId = $attributeId;
        $this->dispatch('modal-show', name: 'attribute-value-modal');
    }

    #[On('edit-value')]
    public function editValue($id)
    {
        $val = AttributeValue::findOrFail($id);
        $this->valueId = $id;
        $this->attributeId = $val->attribute_id;
        $this->value = $val->value;
        $this->label = $val->label;
        $this->slug = $val->slug;
        $this->color_code = $val->color_code;
        $this->is_active = $val->is_active;
        $this->sort_order = $val->sort_order;

        $this->dispatch('modal-show', name: 'attribute-value-modal');
    }

    public function save()
    {
        $rules = [
            'value' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:50',
            'image_path' => 'nullable|image|max:1024',
            'sort_order' => 'integer',
        ];

        $validated = $this->validate($rules);

        if (empty($this->slug)) {
            $validated['slug'] = Str::slug($this->value);
        }

        $validated['attribute_id'] = $this->attributeId;
        $validated['is_active'] = $this->is_active;

        if ($this->image_path) {
            $validated['image_path'] = $this->image_path->store('attributes/swatches', 'public');
        }

        AttributeValue::updateOrCreate(['id' => $this->valueId], $validated);

        $this->dispatch('modal-close', name: 'attribute-value-modal');
        $this->dispatch('refresh-values'); // Notify the parent Edit page
    }
}; ?>

<flux:modal name="attribute-value-modal" class="md:w-[500px]">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $valueId ? 'Edit Value' : 'Add New Value' }}</flux:heading>
            <flux:subheading>Define the specific option for this attribute.</flux:subheading>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input label="Label (Display)" wire:model="label" placeholder="e.g. Red" />
                <flux:input label="Value (Internal/Code)" wire:model="value" placeholder="e.g. red-01" />
            </div>

            <flux:input label="Slug" wire:model="slug" placeholder="auto-generated" />

            <div
                class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 space-y-4">
                <flux:heading size="sm">Visual Representation</flux:heading>

                <flux:input type="color" label="Color Hex Code" wire:model="color_code" />

                <div class="space-y-2">
                    <flux:label>Swatch Image</flux:label>
                    @if ($image_path)
                        <img src="{{ $image_path->temporaryUrl() }}" class="w-12 h-12 rounded border mb-2">
                    @endif
                    <flux:input type="file" wire:model="image_path" size="sm" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <flux:input type="number" label="Sort Order" wire:model="sort_order" class="w-24" />
                <flux:checkbox label="Active" wire:model="is_active" class="mt-6" />
            </div>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Save Value</flux:button>
        </div>
    </form>
</flux:modal>

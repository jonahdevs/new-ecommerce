<?php
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Livewire\Forms\Admin\AttributeForm;
use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public AttributeForm $form;
    public Attribute $attribute;

    public function mount(Attribute $attribute)
    {
        $this->attribute = $attribute;
        $this->form->setAttribute($attribute);
    }

    #[On('refresh-values')]
    public function refresh()
    {
        $this->attribute->load('attributeValues');
    }

    public function save()
    {
        $this->form->update();
        session()->flash('status', 'Attribute updated.');
    }

    public function deleteValue($id)
    {
        AttributeValue::findOrFail($id)->delete();
        $this->refresh();
    }
}; ?>

<div>

    <flux:heading size="xl">Edit Attribute: {{ $attribute->name }}</flux:heading>


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-6">
            <form wire:submit="save" class="space-y-6">
                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:input label="Name" wire:model="form.name" />
                    <flux:select label="Type" wire:model="form.type">
                        <option value="select">Dropdown</option>
                        <option value="color">Color Picker</option>
                        <option value="swatch">Image Swatch</option>
                        <option value="button">Button</option>
                    </flux:select>
                    <flux:switch label="Used for Variations" wire:model="form.used_for_variations" />
                    <flux:button type="submit" variant="primary" class="w-full">Update Info</flux:button>
                </section>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl">
                <div class="flex justify-between items-center mb-6">
                    <flux:heading size="lg">Attribute Values</flux:heading>
                    <flux:button icon="plus" size="sm" variant="subtle"
                        wire:click="$dispatchTo('admin.attributes.value-modal', 'createValue', { attributeId: {{ $attribute->id }} })">
                        Add Value
                    </flux:button>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Label</flux:table.column>
                        <flux:table.column>Visual</flux:table.column>
                        <flux:table.column align="end">Actions</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($attribute->values->sortBy('sort_order') as $val)
                            <flux:table.row :key="$val->id">
                                <flux:table.cell>
                                    <span class="font-medium">{{ $val->label }}</span>
                                    <div class="text-xs text-zinc-500">{{ $val->value }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($attribute->type === 'color')
                                        <div class="w-6 h-6 rounded-full border"
                                            style="background-color: {{ $val->color_code }}"></div>
                                    @elseif($attribute->type === 'swatch' && $val->image_path)
                                        <img src="{{ asset('storage/' . $val->image_path) }}"
                                            class="w-6 h-6 rounded border">
                                    @else
                                        <flux:text size="sm">N/A</flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button variant="ghost" size="sm" icon="pencil-square"
                                        wire:click="$dispatchTo('admin.attributes.value-modal', 'editValue', { id: {{ $val->id }} })" />
                                    <flux:button variant="ghost" size="sm" icon="trash" color="red"
                                        wire:click="deleteValue({{ $val->id }})" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </section>
        </div>
    </div>

    {{-- The Modal Component we discussed --}}
    <livewire:admin.attribute-value-modal />
</div>

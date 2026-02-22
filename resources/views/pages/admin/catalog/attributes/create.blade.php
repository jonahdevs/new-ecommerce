<?php
use App\Livewire\Forms\Admin\AttributeForm;
use Livewire\Component;

new class extends Component {
    public AttributeForm $form;

    public function save()
    {
        $attribute = $this->form->store();
        session()->flash('status', 'Attribute defined. Now add your values.');
        return redirect()->route('admin.attributes.edit', $attribute);
    }
}; ?>

<div>
    <div>
        <flux:heading size="xl">Create Attribute</flux:heading>
        <flux:subheading>Define how this attribute will behave across products.</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-white dark:bg-zinc-900 border rounded-xl">
            <flux:input label="Name" wire:model="form.name" placeholder="e.g. Color or Size" />
            <flux:input label="Slug" wire:model="form.slug" placeholder="auto-generated" />

            <flux:select label="Display Type" wire:model="form.type">
                <option value="select">Dropdown (Select)</option>
                <option value="radio">Radio Buttons</option>
                <option value="color">Color Picker</option>
                <option value="swatch">Image Swatch</option>
                <option value="button">Button/Label</option>
            </flux:select>

            <flux:input type="number" label="Sort Order" wire:model="form.sort_order" />
        </div>

        <div class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
            <flux:heading size="sm">Configuration & Visibility</flux:heading>
            <div class="flex flex-wrap gap-6">
                <flux:switch label="Active" wire:model="form.is_active" description="Enable for use in store" />
                <flux:switch label="Visible" wire:model="form.is_visible" description="Show on product page specs" />
                <flux:switch label="Variation Use" wire:model="form.used_for_variations"
                    description="Can generate unique SKUs" />
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:button href="{{ route('admin.attributes.index') }}" variant="ghost">Cancel</flux:button>
            <flux:button type="submit" variant="primary">Create & Add Values</flux:button>
        </div>
    </form>
</div>

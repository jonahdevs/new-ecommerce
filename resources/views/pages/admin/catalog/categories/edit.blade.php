<?php

use App\Models\Category;
use App\Livewire\Forms\Admin\CategoryForm;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public CategoryForm $form;
    public Category $category;

    public function mount(Category $category)
    {
        $this->category = $category;
        $this->form->setCategory($category);
    }

    public function save()
    {
        $this->form->update();
        session()->flash('status', 'Category updated successfully.');
        return redirect()->route('admin.categories');
    }

    public function with()
    {
        return [
            'parents' => Category::where('id', '!=', $this->category->id)->orderBy('name')->get(),
        ];
    }
}; ?>

<div>

    <flux:heading size="xl">Edit Category: {{ $category->name }}</flux:heading>
    <flux:subheading>Update images, SEO, and hierarchy</flux:subheading>


    <flux:separator class="my-6" />

    <form wire:submit="save" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="md:col-span-2 space-y-6">
                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">General Information</flux:heading>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="Category Name" wire:model="form.name" />
                        <flux:input label="Slug" wire:model="form.slug" placeholder="auto-generated" />
                    </div>
                    <flux:textarea label="Description" wire:model="form.description" rows="4" />

                    <flux:select label="Parent Category" wire:model="form.parent_id">
                        <option value="">Root (No Parent)</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </flux:select>
                </section>

                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">SEO Metadata</flux:heading>
                    <flux:input label="Meta Title" wire:model="form.meta_title" />
                    <flux:textarea label="Meta Description" wire:model="form.meta_description" />
                </section>
            </div>

            <div class="space-y-6">
                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">Visibility</flux:heading>
                    <flux:switch label="Active Status" wire:model="form.is_active" />
                    <flux:switch label="Featured Category" wire:model="form.is_featured" />
                    <flux:switch label="Show in Navbar" wire:model="form.show_in_navbar" />
                </section>

                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">Icons & Media</flux:heading>

                    <div class="space-y-2">
                        <flux:label>Category Icon (Image)</flux:label>
                        @if ($form->image_icon)
                            <img src="{{ $form->image_icon->temporaryUrl() }}" class="w-16 h-16 rounded border">
                        @elseif($category->image_icon)
                            <img src="{{ asset('storage/' . $category->image_icon) }}" class="w-16 h-16 rounded border">
                        @endif
                        <flux:input type="file" wire:model="form.image_icon" size="sm" />
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="space-y-2">
                        <flux:textarea label="Icon SVG Code" wire:model.live="form.icon_svg"
                            placeholder="<svg>...</svg>" rows="3" />
                        @if ($form->icon_svg)
                            <div class="p-2 border rounded bg-zinc-50 w-12 h-12 flex items-center justify-center">
                                {!! $form->icon_svg !!}
                            </div>
                        @endif
                    </div>
                </section>

                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:label>Category Banner</flux:label>
                    @if ($form->image_path)
                        <img src="{{ $form->image_path->temporaryUrl() }}"
                            class="w-full aspect-video rounded border object-cover">
                    @elseif($category->image_path)
                        <img src="{{ asset('storage/' . $category->image_path) }}"
                            class="w-full aspect-video rounded border object-cover">
                    @endif
                    <flux:input type="file" wire:model="form.image_path" />
                </section>
            </div>
        </div>

        <div class="flex justify-end gap-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border">
            <flux:button variant="ghost" href="{{ route('admin.categories') }}">Discard Changes</flux:button>
            <flux:button type="submit" variant="primary">Save Category</flux:button>
        </div>
    </form>
</div>

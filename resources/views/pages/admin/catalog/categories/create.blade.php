<?php
use App\Models\Category;
use App\Livewire\Forms\Admin\CategoryForm;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public CategoryForm $form;

    public function save()
    {
        $this->form->store();
        session()->flash('status', 'Category created successfully.');
        return redirect()->route('admin.categories');
    }

    public function with()
    {
        return [
            'parents' => Category::orderBy('name')->get(),
        ];
    }
}; ?>

<div>

    <flux:heading size="xl">Create New Category</flux:heading>
    <flux:subheading>Define your category structure, media, and SEO</flux:subheading>


    <flux:separator class="my-6" />

    <form wire:submit="save" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="md:col-span-2 space-y-6">
                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">General Information</flux:heading>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="Category Name" wire:model="form.name" placeholder="e.g. Smartphones" />
                        <flux:input label="Slug (Optional)" wire:model="form.slug" placeholder="auto-generated" />
                    </div>
                    <flux:textarea label="Description" wire:model="form.description" rows="4"
                        placeholder="Briefly describe this category..." />

                    <flux:select label="Parent Category" wire:model="form.parent_id"
                        placeholder="Choose a parent (optional)...">
                        <option value="">Root (No Parent)</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </flux:select>
                </section>

                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">SEO Metadata</flux:heading>
                    <flux:input label="Meta Title" wire:model="form.meta_title"
                        placeholder="Best Smartphones 2026..." />
                    <flux:textarea label="Meta Description" wire:model="form.meta_description" />

                    {{-- Basic keywords input - handled as array in Form Object --}}
                    <flux:input label="Meta Keywords (Comma separated)" placeholder="phones, mobile, tech"
                        wire:blur="$set('form.meta_keywords', $event.target.value.split(','))" />
                </section>
            </div>

            <div class="space-y-6">
                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">Visibility & Placement</flux:heading>
                    <flux:switch label="Active Status" wire:model="form.is_active"
                        description="Visible on the storefront" />
                    <flux:switch label="Featured Category" wire:model="form.is_featured"
                        description="Show in 'Featured' sections" />
                    <flux:switch label="Show in Navbar" wire:model="form.show_in_navbar"
                        description="Include in main navigation" />
                    <flux:input type="number" label="Sort Order" wire:model="form.sort_order" />
                </section>

                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:heading size="lg">Icons & Media</flux:heading>

                    <div class="space-y-2">
                        <flux:label>Category Icon (Image)</flux:label>
                        @if ($form->image_icon)
                            <div class="relative w-16 h-16">
                                <img src="{{ $form->image_icon->temporaryUrl() }}"
                                    class="w-16 h-16 rounded border shadow-sm">
                                <button type="button" wire:click="$set('form.image_icon', null)"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-lg">
                                    <flux:icon name="x-mark" variant="micro" />
                                </button>
                            </div>
                        @endif
                        <flux:input type="file" wire:model="form.image_icon" size="sm" />
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="space-y-2">
                        <flux:textarea label="Icon SVG Code" wire:model.live="form.icon_svg"
                            placeholder="<svg>...</svg>" rows="3" />
                        @if ($form->icon_svg)
                            <div
                                class="p-2 border rounded bg-zinc-50 dark:bg-zinc-800 w-12 h-12 flex items-center justify-center">
                                {!! $form->icon_svg !!}
                            </div>
                        @endif
                    </div>
                </section>

                <section class="p-6 bg-white dark:bg-zinc-900 border rounded-xl space-y-4">
                    <flux:label>Category Banner (Large Image)</flux:label>
                    @if ($form->image_path)
                        <div class="relative">
                            <img src="{{ $form->image_path->temporaryUrl() }}"
                                class="w-full aspect-video rounded border object-cover shadow-sm">
                            <button type="button" wire:click="$set('form.image_path', null)"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1">
                                <flux:icon name="x-mark" variant="micro" />
                            </button>
                        </div>
                    @endif
                    <flux:input type="file" wire:model="form.image_path" />
                </section>
            </div>
        </div>

        <div class="flex justify-end gap-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border">
            <flux:button variant="ghost" href="{{ route('admin.categories') }}">Cancel</flux:button>
            <flux:button type="submit" variant="primary">Create Category</flux:button>
        </div>
    </form>
</div>

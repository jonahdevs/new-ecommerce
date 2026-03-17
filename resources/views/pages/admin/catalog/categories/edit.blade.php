<?php

use App\Models\Category;
use App\Livewire\Forms\Admin\CategoryForm;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Illuminate\Validation\ValidationException;

new class extends Component {
    use WithFileUploads;

    public CategoryForm $form;
    public Category $category;

    public function mount(Category $category): void
    {
        $this->category = $category;

        // Eager load placements so fromCategory() doesn't N+1
        $category->load('placements');
        $this->form->fromCategory($category);
    }

    public function updatedFormName(string $value): void
    {
        // Only auto-update slug if it still matches the original name's slug
        // i.e. the user hasn't manually customised it
        $originalSlug = \Illuminate\Support\Str::slug($this->category->name);

        if ($this->form->slug === $originalSlug) {
            $this->form->slug = \Illuminate\Support\Str::slug($value);
        }
    }

    public function save(): void
    {
        try {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', message: 'Category updated successfully!');
            $this->redirectRoute('admin.catalog.categories.index', navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('notify', variant: 'warning', message: 'Please correct the highlighted fields and try again.');
            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Error updating category: ' . $th->getMessage(), ['exception' => $th]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to update category. Please try again.');
        }
    }

    #[Computed]
    public function parents()
    {
        // Exclude self and own descendants to prevent circular parent assignment
        return Category::where('id', '!=', $this->category->id)
            ->whereNotIn('parent_id', [$this->category->id])
            ->orderBy('name')
            ->get();
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.catalog.categories.index')" wire:navigate>Categories
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $category->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mt-2 mb-6">
        <flux:heading size="xl">Edit Category</flux:heading>

        <flux:badge :color="$category->status->color()" size="lg">
            {{ $category->status->label() }}
        </flux:badge>
    </div>

    <form wire:submit="save" class="space-y-5">
        @include('pages.admin.catalog.categories._form-fields')

        <flux:card class="flex justify-between items-center bg-zinc-50">
            {{-- Danger zone --}}
            <flux:button type="button" variant="danger" size="sm" icon="trash"
                wire:click="$dispatch('open-delete-modal', { id: {{ $category->id }}, name: '{{ $category->name }}' })"
                class="cursor-pointer">
                Delete Category
            </flux:button>

            <div class="flex gap-3">
                <flux:button type="button" variant="ghost" :href="route('admin.catalog.categories.index')"
                    wire:navigate class="cursor-pointer">
                    Discard Changes
                </flux:button>
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    Save Category
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>

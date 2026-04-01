<?php

use App\Models\Tag;
use Livewire\Component;
use Livewire\Attributes\Title;
use App\Livewire\Forms\Admin\TagForm;
use Illuminate\Validation\ValidationException;

new #[Title('Edit Tag')] class extends Component {
    public TagForm $form;
    public Tag $tag;

    public function mount(Tag $tag): void
    {
        $this->tag = $tag;
        $this->form->setTag($tag);
    }

    public function save(): void
    {
        try {
            $this->form->update();

            $this->dispatch('notify', title: 'Tag Updated', variant: 'success', message: 'The tag has been updated successfully');

            $this->redirectRoute('admin.catalog.tags.index', navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('notify', title: 'Validation Error', variant: 'warning', message: 'Please correct the highlighted fields and try again');

            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Error updating tag: ' . $th->getMessage(), ['exception' => $th]);

            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Failed to update tag. Please try again');
        }
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.catalog.tags.index')" wire:navigate>Tags</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mt-2 mb-6">
        <flux:heading size="xl">Edit Tag</flux:heading>
        <flux:subheading size="md" class="text-zinc-500">Make changes to the tag details. Remember to save your
            changes when you're done.</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6 space-y-5">
        @include('pages.admin.catalog.tags._form-fields')

        <flux:card class="flex gap-3 justify-end bg-zinc-50 dark:bg-zinc-900">
            <flux:button variant="ghost" :href="route('admin.catalog.tags.index')" wire:navigate>
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary">
                Update Tag
            </flux:button>
        </flux:card>
    </form>
</div>

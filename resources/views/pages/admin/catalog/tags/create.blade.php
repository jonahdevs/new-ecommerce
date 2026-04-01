<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Livewire\Forms\Admin\TagForm;
use Illuminate\Validation\ValidationException;

new #[Title('Create Tag')] class extends Component {
    public TagForm $form;

    public function save(): void
    {
        try {
            $this->form->store();

            $this->dispatch('notify', title: 'Tag Created', variant: 'success', message: 'The tag has been created successfully');

            $this->redirectRoute('admin.catalog.tags.index', navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('notify', title: 'Validation Error', variant: 'warning', message: 'Please correct the highlighted fields and try again');

            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Error creating tag: ' . $th->getMessage(), ['exception' => $th]);

            $this->dispatch('notify', title: 'Creation Failed', variant: 'danger', message: 'Failed to create tag. Please try again');
        }
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.catalog.tags.index')" wire:navigate>Tags</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mt-2 mb-6">
        <flux:heading size="xl">Create Tag</flux:heading>
        <flux:subheading size="md" class="text-zinc-500">Add a new tag to your store. You can edit it later if
            you need to.</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6 space-y-5">
        @include('pages.admin.catalog.tags._form-fields')

        <flux:card class="flex gap-3 justify-end bg-zinc-50 dark:bg-zinc-900">
            <flux:button variant="ghost" :href="route('admin.catalog.tags.index')" wire:navigate>
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary">
                Create Tag
            </flux:button>
        </flux:card>
    </form>
</div>

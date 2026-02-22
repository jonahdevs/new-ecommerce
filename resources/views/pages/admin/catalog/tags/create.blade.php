<?php
use App\Models\Tag;
use Livewire\Component;
use Livewire\Attributes\Title;
use App\Livewire\Forms\Admin\TagForm;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

new #[Title('Create Tag')] class extends Component {
    public TagForm $form;

    public bool $autoGenerateSlug = true;

    public function updatedFormName(): void
    {
        if ($this->autoGenerateSlug) {
            $this->form->slug = Str::slug($this->form->name);
        }
    }

    public function updatedFormSlug(): void
    {
        // If the user manually edits the slug, stop auto-generating
        $this->autoGenerateSlug = false;
    }

    public function save(): void
    {
        try {
            $tag = $this->form->store();
            $this->dispatch('notify', variant: 'success', message: 'Tag created successfully!');
            $this->redirectRoute('admin.tags.index', navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('notify', variant: 'warning', message: 'Please correct the highlighted fields and try again.');
            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Error creating tag: ' . $th->getMessage(), ['exception' => $th]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to create tag. Please try again.');
        }
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.tags.index')" wire:navigate>Tags</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Create Tag</flux:heading>

    <form wire:submit="save" class="mt-6 space-y-5">
        @include('pages.admin.catalog.tags._form-fields')

        <flux:card class="flex gap-3 justify-end bg-zinc-50 dark:bg-zinc-800">
            <flux:button variant="ghost" :href="route('admin.tags.index')" wire:navigate class="cursor-pointer">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">
                Create Tag
            </flux:button>
        </flux:card>
    </form>
</div>

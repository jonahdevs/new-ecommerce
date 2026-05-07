<?php
use App\Models\Attribute;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};
use App\Livewire\Forms\Admin\AttributeForm;
use Flux\Flux;

new #[Title('Product Attributes')] class extends Component {
    use WithPagination;

    public AttributeForm $form;

    public bool $editing = false;
    public $search = '';

    public function delete($id)
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->delete();
        $this->dispatch('notify', title: 'Attribute Deleted', variant: 'success', message: 'Attribute deleted successfully.');
    }

    public function editAttribute($id): void
    {
        $attribute = Attribute::findOrFail($id);
        $this->form->setAttribute($attribute);
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->form->reset();
        $this->editing = false;
    }

    public function createAttribute()
    {
        try {
            if ($this->editing) {
                $this->form->update();
                $this->editing = false;
                $this->dispatch('notify', title: 'Attribute Updated', variant: 'success', message: 'Attribute updated.');
            } else {
                $this->form->store();
                $this->dispatch('notify', title: 'Attribute Created', variant: 'success', message: 'Attribute created.');
            }

            $this->form->reset();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save attribute.', [
                'exception' => $e->getMessage(),
                'attribute_id' => $this->form->attribute?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    #[Computed]
    public function productAttributes()
    {
        return Attribute::withCount('values')->with('values')->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))->orderBy('sort_order')->paginate(10);
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Attributes</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Attributes</flux:heading>
    <flux:subheading>Manage options like Size, Color, and Material for your products</flux:subheading>

    <div class="flex items-start gap-8 space-y-5 mt-6">

        <form wire:submit="createAttribute"
            class="space-y-5 w-full max-w-md **:data-flux-description:mt-0! **:data-flux-error:mt-0!">

            <div class="flex items-center justify-between">
                <flux:heading size="lg">
                    {{ $editing ? 'Edit Attribute' : 'Add new Attribute' }}
                </flux:heading>
                @if ($editing)
                    <flux:badge color="yellow" size="sm">Editing</flux:badge>
                @endif
            </div>

            <flux:input label="Name" wire:model="form.name" placeholder="e.g. Color or Size"
                description:trailing="Name for the attribute (shown on the front-end)" />

            <flux:input label="Slug" wire:model="form.slug"
                description:trailing="Unique slug/reference for attribute; must be no more than 28 characters" />

            <flux:select label="Type" wire:model="form.watch_type"
                description:trailing="Choose how this attribute should appear in frontend">
                <flux:select.option value="select">Select</flux:select.option>
                <flux:select.option value="label">Label</flux:select.option>
                <flux:select.option value="color">Color</flux:select.option>
                <flux:select.option value="image">Image</flux:select.option>
            </flux:select>

            <flux:select label="Shape" wire:model.live="form.watch_shape">
                <flux:select.option value="default">Default</flux:select.option>
                <flux:select.option value="square">Square</flux:select.option>
                <flux:select.option value="rounded-corners">Rounded Corners</flux:select.option>
                <flux:select.option value="circle">Circle</flux:select.option>
            </flux:select>

            @if ($form->watch_shape != 'default')
                <flux:input type="number" label="Size (optional)" wire:model="form.watch_size" />
            @endif

            <flux:input type="number" label="Sort Order" wire:model="form.sort_order" />

            <flux:checkbox wire:model="form.is_active" label="Active" />

            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary">
                    {{ $editing ? 'Update Attribute' : 'Add Attribute' }}
                </flux:button>

                @if ($editing)
                    <flux:button type="button" wire:click="cancelEdit" variant="ghost">
                        Cancel
                    </flux:button>
                @endif
            </div>
        </form>

        <flux:card
            class="p-0 flex-1 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800 overflow-hidden overflow-x-auto">
            <flux:table :paginate="$this->productAttributes">
                <flux:table.columns>
                    <flux:table.column class="ps-4!">Name</flux:table.column>
                    <flux:table.column>Slug</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Values</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->productAttributes as $attribute)
                        <flux:table.row :key="$attribute->id" class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <flux:table.cell class="ps-4!">
                                <flux:text>{{ $attribute->name }}</flux:text>

                                <div
                                    class="flex items-center divide-x mt-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300 ease-in-out">
                                    <button type="button" wire:click="editAttribute({{ $attribute->id }})"
                                        class="text-sm pe-2 text-secondary dark:text-secondary-hover hover:underline cursor-pointer">
                                        Edit
                                    </button>

                                    <button type="button"
                                        wire:confirm="Are you sure? This will remove the attribute and all its values."
                                        wire:click="delete({{ $attribute->id }})"
                                        class="text-sm ps-2 text-red-500 hover:underline cursor-pointer">
                                        Delete
                                    </button>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text>{{ $attribute->slug }}</flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc" class="capitalize">
                                    {{ $attribute->watch_type }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" :color="$attribute->is_active ? 'green' : 'red'">
                                    {{ $attribute->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                @php
                                    $values = $attribute->values;
                                    $visibleValues = $values->take(4);
                                    $remainingCount = max(0, $values->count() - 4);
                                @endphp

                                <div>
                                    @foreach ($visibleValues as $value)
                                        <span>
                                            {{ $value->value }}@if (!$loop->last)
                                                ,
                                            @endif
                                        </span>
                                    @endforeach

                                    @if ($remainingCount > 0)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-full ml-1">
                                            +{{ $remainingCount }}
                                        </span>
                                    @endif
                                </div>

                                <flux:link class="text-xs! "
                                    href="{{ route('admin.catalog.attributes.values', $attribute) }}" wire:navigate>
                                    Configure values
                                </flux:link>
                            </flux:table.cell>
                        </flux:table.row>

                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <flux:icon name="tag" class="w-10 h-10 text-zinc-400" />
                                        <flux:heading size="lg" class="text-zinc-700">No Attributes Found
                                        </flux:heading>
                                        <flux:text class="text-sm text-zinc-500 max-w-md">
                                            You haven't created any product attributes yet.
                                            Attributes help you define product variations like size, color, or material.
                                        </flux:text>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>

        <style>
            [data-flux-pagination] {
                padding-inline: 1rem;
                padding-bottom: 1rem;
            }
        </style>
    </div>

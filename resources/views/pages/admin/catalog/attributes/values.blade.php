<?php
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Livewire\Forms\Admin\AttributeValueForm;
use Livewire\Component;
use Livewire\Attributes\{Title, Computed};

new class extends Component {
    public AttributeValueForm $form;
    public Attribute $attribute;
    public bool $editing = false;

    public function mount(Attribute $attribute): void
    {
        $this->attribute = $attribute;
    }

    #[Computed]
    public function attributeValues()
    {
        return $this->attribute->values()->orderBy('sort_order')->paginate(15);
    }

    public function editValue(int $id): void
    {
        $attributeValue = AttributeValue::findOrFail($id);
        $this->form->setAttributeValue($attributeValue);
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->form->reset();
        $this->editing = false;
    }

    public function save(): void
    {
        try {
            if ($this->editing) {
                $this->form->update();
                $this->editing = false;
                $this->dispatch('notify', variant: 'success', message: 'Value updated.');
            } else {
                $this->form->store($this->attribute->id);
                $this->dispatch('notify', variant: 'success', message: 'Value added.');
            }

            $this->form->reset();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save attribute value.', [
                'exception' => $e->getMessage(),
                'attribute_id' => $this->attribute->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function deleteValue(int $id): void
    {
        AttributeValue::findOrFail($id)->delete();
        $this->dispatch('notify', variant: 'success', message: 'Value deleted.');
    }

    public function handleSort(string $id, string $position): void
    {
        try {
            $values = $this->attribute->values()->orderBy('sort_order')->get();

            $sorted = $values->filter(fn($v) => $v->id != $id)->values();
            $dragged = $values->firstWhere('id', $id);
            $sorted->splice((int) $position, 0, [$dragged]);

            // Single query instead of N updates
            $cases = $sorted->map(fn($v, $index) => "WHEN {$v->id} THEN {$index}")->join(' ');
            $ids = $sorted->pluck('id')->join(',');

            DB::statement("UPDATE attribute_values SET sort_order = CASE id {$cases} END WHERE id IN ({$ids})");
        } catch (\Throwable $th) {
            Log::error('Attribute value sort update failed.', [
                'attribute_id' => $this->attribute?->id,
                'user_id' => auth()->id(),
                'exception_message' => $th->getMessage(),
            ]);
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.catalog.attributes.index')" wire:navigate>Attributes
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Values</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">{{ $attribute->name }} Values</flux:heading>

    <div class="flex items-start gap-8">
        <form wire:submit="save"
            class="space-y-5 w-full max-w-md [&_[data-flux-description]]:mt-0! [&_[data-flux-error]]:mt-0!">

            <div class="flex items-center justify-between">
                <flux:heading size="lg">
                    {{ $editing ? 'Edit Value' : 'Add new ' . $attribute->name }}
                </flux:heading>
                @if ($editing)
                    <flux:badge color="yellow" size="sm">Editing</flux:badge>
                @endif
            </div>

            <flux:input label="Name" wire:model="form.value"
                description:trailing="The name is how it appears on your site" />

            <flux:input label="Slug" wire:model="form.slug"
                description:trailing="The 'slug' is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens." />

            <flux:textarea label="Description" wire:model="form.description" />

            @if ($attribute->watch_type == 'color')
                <flux:input label="Color Code" wire:model="form.color_code" placeholder="#FF0000" type="color"
                    description:trailing="Hex code for the color (e.g. #FF0000 for red)" />
            @endif

            <flux:input type="number" label="Sort Order" wire:model="form.sort_order" min="0" />

            <flux:checkbox wire:model="form.is_active" label="Active" />

            <div class="flex items-center gap-3">
                <flux:button type="submit" icon="{{ $editing ? 'check' : 'plus' }}" variant="primary"
                    class="cursor-pointer">
                    {{ $editing ? 'Update ' . $attribute->name : 'Add new ' . $attribute->name }}
                </flux:button>

                @if ($editing)
                    <flux:button type="button" wire:click="cancelEdit" variant="ghost" class="cursor-pointer">
                        Cancel
                    </flux:button>
                @endif
            </div>
        </form>

        <flux:card class="p-0 flex-1">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="ps-4!">Preview</flux:table.column>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                    <flux:table.column>Slug</flux:table.column>
                    <flux:table.column>Sort Order</flux:table.column>
                    <flux:table.column class="pe-4!">Count</flux:table.column>
                </flux:table.columns>

                <flux:table.rows wire:sort="handleSort">
                    @forelse ($this->attributeValues as $val)
                        <flux:table.row :key="$val->id" wire:sort:item="{{ $val->id }}"
                            class="group hover:bg-zinc-50">

                            {{-- Preview Column --}}
                            <flux:table.cell class="ps-4!">
                                @if ($attribute->watch_type === 'color' && $val->color_code)
                                    <div class="w-6 h-6 rounded-full border"
                                        style="background-color: {{ $val->color_code }}">
                                    </div>
                                @elseif ($attribute->watch_type === 'swatch' && $val->image_path)
                                    <img src="{{ asset('storage/' . $val->image_path) }}"
                                        class="w-6 h-6 rounded border object-cover">
                                @else
                                    <flux:text size="sm" class="text-zinc-400">—</flux:text>
                                @endif
                            </flux:table.cell>

                            {{-- Value --}}
                            <flux:table.cell>
                                <flux:heading>{{ $val->value }}</flux:heading>

                                <div
                                    class="flex items-center divide-x mt-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300 ease-in-out">
                                    <button type="button" wire:click="editValue({{ $val->id }})"
                                        class="text-sm pe-2 text-sheffield-blue hover:underline cursor-pointer">
                                        Edit
                                    </button>

                                    <button type="button"
                                        wire:confirm="Are you sure? This will permanently delete this value."
                                        wire:click="deleteValue({{ $val->id }})"
                                        class="text-sm ps-2 text-red-500 hover:underline cursor-pointer">
                                        Delete
                                    </button>
                                </div>
                            </flux:table.cell>

                            {{-- Description --}}
                            <flux:table.cell>
                                {{ $val->description ?: '—' }}
                            </flux:table.cell>

                            {{-- Slug --}}
                            <flux:table.cell>
                                {{ $val->slug }}
                            </flux:table.cell>

                            {{-- Sort Order --}}
                            <flux:table.cell>
                                {{ $val->sort_order }}
                            </flux:table.cell>

                            {{-- Usage Count --}}
                            <flux:table.cell class="pe-4!">
                                0
                            </flux:table.cell>

                        </flux:table.row>

                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center space-y-3">
                                    <flux:icon name="tag" class="w-10 h-10 text-zinc-400" />
                                    <flux:heading size="lg" class="text-zinc-700">No Values Created</flux:heading>
                                    <flux:text class="text-sm text-zinc-500 max-w-md">
                                        This attribute does not have any values yet.
                                        Add values to enable product variations.
                                    </flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>

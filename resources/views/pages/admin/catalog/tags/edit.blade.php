<?php
use App\Models\Tag;
use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;

new class extends Component {
    public Tag $tag;

    public $name = '';
    public $slug = '';
    public $description = '';
    public $color = '#6366F1';
    public $is_active = true;
    public $sort_order = 0;

    public function mount(Tag $tag)
    {
        $this->tag = $tag;
        $this->fill($tag->only(['name', 'slug', 'description', 'color', 'is_active', 'sort_order']));
    }

    public function title()
    {
        return "Edit Tag: {$this->tag->name}";
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $this->tag->id,
            'slug' => 'required|string|max:255|unique:tags,slug,' . $this->tag->id,
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $this->tag->update($validated);

        session()->flash('status', 'Tag updated successfully.');

        return $this->redirect(route('admin.tags'), navigate: true);
    }
};
?>

<div>
    <flux:header>
        <flux:heading size="xl">{{ $this->title() }}</flux:heading>

        <flux:button variant="ghost" href="{{ route('admin.tags') }}" icon="arrow-left" wire:navigate>
            Back to Tags
        </flux:button>
    </flux:header>

    <div class="max-w-2xl mt-6 space-y-6">
        {{-- Tag Stats --}}
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-zinc-600">Products using this tag</div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">
                        {{ $tag->products()->count() }}
                    </div>
                </div>

                <div class="w-12 h-12 rounded-full border-2" style="background-color: {{ $tag->color }}">
                </div>
            </div>
        </flux:card>

        <form wire:submit="save" class="space-y-6">
            <flux:card class="space-y-5">
                {{-- Name --}}
                <flux:field>
                    <flux:label>Tag Name *</flux:label>
                    <flux:input wire:model="name" />
                    <flux:error name="name" />
                </flux:field>

                {{-- Slug --}}
                <flux:field>
                    <flux:label>Slug *</flux:label>
                    <flux:input wire:model="slug" />
                    <flux:error name="slug" />
                    <flux:description>URL-friendly version of the name.</flux:description>
                </flux:field>

                {{-- Description --}}
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="3" />
                    <flux:error name="description" />
                </flux:field>

                {{-- Color --}}
                <flux:field>
                    <flux:label>Color *</flux:label>
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="color"
                            class="h-10 w-20 rounded border border-zinc-300 cursor-pointer" />
                        <flux:input wire:model="color" class="flex-1" />
                    </div>
                    <flux:error name="color" />
                </flux:field>

                {{-- Sort Order --}}
                <flux:field>
                    <flux:label>Sort Order</flux:label>
                    <flux:input wire:model="sort_order" type="number" min="0" />
                    <flux:error name="sort_order" />
                </flux:field>

                {{-- Active Status --}}
                <flux:field>
                    <div class="flex items-center gap-3">
                        <flux:switch wire:model="is_active" />
                        <flux:label>Active</flux:label>
                    </div>
                    <flux:description>Inactive tags won't be shown to customers.</flux:description>
                </flux:field>
            </flux:card>

            {{-- Actions --}}
            <div class="flex gap-3 justify-end">
                <flux:button variant="ghost" href="{{ route('admin.tags') }}" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Update Tag
                </flux:button>
            </div>
        </form>
    </div>
</div>

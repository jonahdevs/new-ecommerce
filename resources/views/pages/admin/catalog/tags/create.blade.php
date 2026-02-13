<?php
use App\Models\Tag;
use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;

new #[Title('Create Tag')] class extends Component {
    public $name = '';
    public $slug = '';
    public $description = '';
    public $color = '#6366F1';
    public $is_active = true;
    public $sort_order = 0;

    public $autoGenerateSlug = true;

    public function updatedName()
    {
        if ($this->autoGenerateSlug) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug()
    {
        $this->autoGenerateSlug = false;
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255|unique:tags,name',
            'slug' => 'required|string|max:255|unique:tags,slug',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Tag::create($validated);

        session()->flash('status', 'Tag created successfully.');

        return $this->redirect(route('admin.tags'), navigate: true);
    }
};
?>

<div>
    <flux:header>
        <flux:heading size="xl">Create Tag</flux:heading>

        <flux:button variant="ghost" href="{{ route('admin.tags') }}" icon="arrow-left" wire:navigate>
            Back to Tags
        </flux:button>
    </flux:header>

    <form wire:submit="save" class="max-w-2xl mt-6 space-y-6">
        <flux:card>
            {{-- Name --}}
            <flux:field>
                <flux:label>Tag Name *</flux:label>
                <flux:input wire:model.live="name" placeholder="e.g., New Arrival, Featured, Sale" variant="filled" />
                <flux:error name="name" />
            </flux:field>

            {{-- Slug --}}
            <flux:field>
                <flux:label>Slug *</flux:label>
                <flux:input wire:model.blur="slug" placeholder="e.g., new-arrival" variant="filled" />
                <flux:error name="slug" />
                <flux:description>URL-friendly version of the name. Leave blank to auto-generate.</flux:description>
            </flux:field>

            {{-- Description --}}
            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" rows="3"
                    placeholder="Optional description for this tag..." variant="filled" />
                <flux:error name="description" />
            </flux:field>

            {{-- Color --}}
            <flux:field>
                <flux:label>Color *</flux:label>
                <div class="flex items-center gap-3">
                    <input type="color" wire:model.live="color"
                        class="h-10 w-20 rounded border border-zinc-300 cursor-pointer" />
                    <flux:input wire:model="color" placeholder="#6366F1" variant="filled" class="flex-1" />
                </div>
                <flux:error name="color" />
                <flux:description>Choose a color to represent this tag.</flux:description>
            </flux:field>

            {{-- Sort Order --}}
            <flux:field>
                <flux:label>Sort Order</flux:label>
                <flux:input wire:model="sort_order" type="number" min="0" variant="filled" />
                <flux:error name="sort_order" />
                <flux:description>Lower numbers appear first. Leave as 0 for default ordering.</flux:description>
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
                Create Tag
            </flux:button>
        </div>
    </form>
</div>

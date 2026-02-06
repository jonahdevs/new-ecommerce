<?php
use App\Models\FreeShippingRule;
use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use Livewire\Attributes\{Title, Computed};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Free Shipping Rules')] class extends Component {
    use WithPagination;

    // Form State
    public string $name = '';
    public ?int $shipping_zone_id = null;
    public ?int $shipping_method_id = null;
    public $min_order_amount = 0;
    public $max_weight = null;
    public $starts_at = null;
    public $ends_at = null;
    public bool $is_active = true;
    public ?int $editingId = null;

    #[Computed]
    public function rules()
    {
        return FreeShippingRule::with(['zone', 'method'])
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::where('is_active', true)->get();
    }

    #[Computed]
    public function methods()
    {
        return ShippingMethod::where('is_active', true)->get();
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'shipping_zone_id' => 'nullable|exists:shipping_zones,id',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'min_order_amount' => 'required|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        FreeShippingRule::updateOrCreate(['id' => $this->editingId], $data);

        Flux::toast($this->editingId ? 'Rule updated.' : 'Rule created.');
        $this->resetForm();
        Flux::modal('rule-modal')->close();
    }

    public function edit($id)
    {
        $rule = FreeShippingRule::findOrFail($id);
        $this->editingId = $rule->id;
        $this->name = $rule->name;
        $this->shipping_zone_id = $rule->shipping_zone_id;
        $this->shipping_method_id = $rule->shipping_method_id;
        $this->min_order_amount = $rule->min_order_amount;
        $this->max_weight = $rule->max_weight;
        $this->starts_at = $rule->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $rule->ends_at?->format('Y-m-d\TH:i');
        $this->is_active = $rule->is_active;

        Flux::modal('rule-modal')->show();
    }

    public function resetForm()
    {
        $this->reset(['name', 'shipping_zone_id', 'shipping_method_id', 'min_order_amount', 'max_weight', 'starts_at', 'ends_at', 'editingId', 'is_active']);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">Free Shipping Rules</flux:heading>
            <flux:subheading>Set thresholds and promotional periods for free delivery.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="gift" wire:click="resetForm" @click="$flux.modal('rule-modal').show()"
            class="cursor-pointer">
            Create Promo
        </flux:button>
    </div>

    <flux:card class="overflow-hidden">
        <flux:table :paginate="$this->rules">
            <flux:table.columns>
                <flux:table.column>Campaign Name</flux:table.column>
                <flux:table.column>Conditions</flux:table.column>
                <flux:table.column>Schedule</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->rules as $rule)
                    <flux:table.row :key="$rule->id">
                        <flux:table.cell class="font-semibold">
                            {{ $rule->name }}
                            <div class="text-[10px] text-zinc-400">
                                {{ $rule->zone?->name ?? 'All Zones' }} • {{ $rule->method?->name ?? 'All Methods' }}
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm">Min: KES {{ number_format($rule->min_order_amount) }}</div>
                            @if ($rule->max_weight)
                                <div class="text-[10px] text-zinc-500">Weight Limit: {{ $rule->max_weight }}kg</div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($rule->starts_at)
                                <div class="text-xs">{{ $rule->starts_at->format('M d, Y') }} -
                                    {{ $rule->ends_at?->format('M d, Y') ?? '∞' }}</div>
                            @else
                                <flux:badge size="sm" color="zinc">Always On</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:switch wire:click="toggleActive({{ $rule->id }})" :checked="$rule->is_active" />
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <flux:button variant="ghost" size="sm" icon="pencil-square"
                                wire:click="edit({{ $rule->id }})" class="cursor-pointer" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="rule-modal" class="md:w-lg space-y-6">
        <flux:heading size="lg" class="text-center">{{ $editingId ? 'Edit' : 'New' }} Shipping Rule</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="name" label="Rule/Promo Name" placeholder="e.g. Valentine's Special" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="shipping_zone_id" label="Zone (Optional)">
                    <option value="">Global (All Zones)</option>
                    @foreach ($this->zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="shipping_method_id" label="Method (Optional)">
                    <option value="">All Methods</option>
                    @foreach ($this->methods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input type="number" wire:model="min_order_amount" label="Min Subtotal (KES)" icon="banknotes" />
                <flux:input type="number" wire:model="max_weight" label="Max Weight (kg)" icon="scale"
                    placeholder="Optional" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input type="datetime-local" wire:model="starts_at" label="Start Date" />
                <flux:input type="datetime-local" wire:model="ends_at" label="End Date" />
            </div>

            <flux:checkbox wire:model="is_active" label="Enable this rule immediately" />

            <div class="flex pt-4">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Rule</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

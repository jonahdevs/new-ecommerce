<?php

use App\Models\Showroom;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Showrooms — Admin')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $city = '';

    public string $country = 'Kenya';

    public string $address = '';

    public string $pobox = '';

    /** Comma-separated in the form; stored as a JSON array. */
    public string $phonesInput = '';

    public string $email = '';

    public bool $is_hq = false;

    public int $sort_order = 0;

    #[Computed]
    public function showrooms(): Collection
    {
        return Showroom::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('city', 'like', '%'.$this->search.'%')
                    ->orWhere('address', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('sort_order')
            ->orderBy('city')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->clearSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->showrooms->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'pobox' => ['nullable', 'string', 'max:100'],
            'phonesInput' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_hq' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->reset(['editingId', 'city', 'country', 'address', 'pobox', 'phonesInput', 'email', 'is_hq', 'sort_order']);
        $this->country = 'Kenya';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->resetValidation();
        $showroom = Showroom::findOrFail($id);
        $this->editingId = $showroom->id;
        $this->city = $showroom->city;
        $this->country = $showroom->country;
        $this->address = $showroom->address;
        $this->pobox = $showroom->pobox ?? '';
        $this->phonesInput = implode(', ', $showroom->phones ?? []);
        $this->email = $showroom->email ?? '';
        $this->is_hq = $showroom->is_hq;
        $this->sort_order = $showroom->sort_order;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        $phones = collect(explode(',', $data['phonesInput']))
            ->map(fn (string $phone): string => trim($phone))
            ->filter()
            ->values()
            ->all();

        if ($phones === []) {
            $this->addError('phonesInput', 'Add at least one phone number.');

            return;
        }

        $payload = [
            'city' => $data['city'],
            'country' => $data['country'],
            'address' => $data['address'],
            'pobox' => $data['pobox'] !== '' ? $data['pobox'] : null,
            'phones' => $phones,
            'email' => $data['email'] !== '' ? $data['email'] : null,
            'is_hq' => $data['is_hq'],
            'sort_order' => $data['sort_order'],
        ];

        if ($this->editingId) {
            Showroom::findOrFail($this->editingId)->update($payload);
            Flux::toast(heading: 'Showroom updated', text: $payload['city'].' has been saved.', variant: 'success');
        } else {
            Showroom::create($payload);
            Flux::toast(heading: 'Showroom added', text: $payload['city'].' is now listed.', variant: 'success');
        }

        $this->showModal = false;
        unset($this->showrooms);
    }

    public function delete(int $id): void
    {
        Showroom::findOrFail($id)->delete();
        unset($this->showrooms);
        Flux::toast(heading: 'Showroom removed', text: 'The location has been deleted.', variant: 'warning');
    }

    public function bulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = Showroom::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        unset($this->showrooms);
        Flux::toast(heading: 'Showrooms removed', text: $count.' location(s) have been deleted.', variant: 'warning');
    }
}; ?>

<div class="space-y-6">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Showrooms</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Showrooms</flux:heading>
            <flux:text class="mt-1">Branch locations shown in the storefront footer.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">Add showroom</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search city or address…"
                icon="magnifying-glass" clearable class="max-w-xs" />
        </div>

        {{-- Bulk action bar --}}
        @if (count($selected) > 0)
            <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 bg-brand-50 px-6 py-2.5 dark:border-zinc-700 dark:bg-brand-500/10">
                <flux:text class="font-medium">{{ count($selected) }} selected</flux:text>
                <flux:button size="sm" variant="ghost" icon="trash-2"
                    wire:click="bulkDelete"
                    wire:confirm="Delete {{ count($selected) }} showroom(s)? This cannot be undone."
                    class="text-red-500! hover:text-red-600!">Delete</flux:button>
                <flux:spacer />
                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Clear</flux:button>
            </div>
        @endif

        <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column class="w-10">
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
                <flux:table.column>City</flux:table.column>
                <flux:table.column>Address</flux:table.column>
                <flux:table.column>Phones</flux:table.column>
                <flux:table.column>Order</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->showrooms as $showroom)
                    <flux:table.row :key="$showroom->id" wire:key="showroom-{{ $showroom->id }}">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" value="{{ $showroom->id }}" />
                        </flux:table.cell>
                        <flux:table.cell variant="strong">
                            {{ $showroom->city }}
                            @if ($showroom->is_hq)
                                <flux:badge color="blue" size="sm" inset="top bottom" class="ml-1">HQ</flux:badge>
                            @endif
                            <span class="block text-xs text-zinc-400">{{ $showroom->country }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">
                            {{ $showroom->address }}
                            @if ($showroom->pobox)
                                <span class="block text-xs text-zinc-400">{{ $showroom->pobox }}</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">{{ implode(' / ', $showroom->phones ?? []) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ $showroom->sort_order }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" tooltip="Edit"
                                    wire:click="openEdit({{ $showroom->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash-2" tooltip="Delete"
                                    wire:click="delete({{ $showroom->id }})"
                                    wire:confirm="Delete the {{ $showroom->city }} showroom?"
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                            @if ($search)
                                No showrooms match your search.
                            @else
                                No showrooms yet. Add your first location.
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- ================================================== --}}
    {{-- SHOWROOM MODAL --}}
    {{-- ================================================== --}}
    <flux:modal wire:model.self="showModal" class="md:w-[560px]" :dismissible="false">
        <flux:heading>{{ $editingId ? 'Edit showroom' : 'New showroom' }}</flux:heading>
        <flux:subheading>A branch location with its address and contact numbers.</flux:subheading>

        <form wire:submit="save" class="mt-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>City</flux:label>
                    <flux:input wire:model="city" placeholder="Nairobi" />
                    <flux:error name="city" />
                </flux:field>
                <flux:field>
                    <flux:label>Country</flux:label>
                    <flux:input wire:model="country" placeholder="Kenya" />
                    <flux:error name="country" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Address</flux:label>
                <flux:input wire:model="address" placeholder="Off Old Mombasa Road…" />
                <flux:error name="address" />
            </flux:field>

            <flux:field>
                <flux:label>P.O. Box</flux:label>
                <flux:input wire:model="pobox" placeholder="Optional" />
                <flux:error name="pobox" />
            </flux:field>

            <flux:field>
                <flux:label>Phone numbers</flux:label>
                <flux:input wire:model="phonesInput" placeholder="+254 713 777 111, +254 713 444 000" />
                <flux:description>Separate multiple numbers with commas.</flux:description>
                <flux:error name="phonesInput" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="email" placeholder="branch@store.com" />
                <flux:error name="email" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Sort order</flux:label>
                    <flux:input type="number" wire:model="sort_order" min="0" />
                    <flux:error name="sort_order" />
                </flux:field>
                <div class="flex items-end pb-2">
                    <flux:checkbox wire:model="is_hq" label="Headquarters" />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
                <flux:button type="submit" variant="primary">{{ $editingId ? 'Save showroom' : 'Add showroom' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

<?php

use App\Models\Showroom;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Showrooms — Admin')] class extends Component
{
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
            ->orderBy('sort_order')
            ->orderBy('city')
            ->get();
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

    <div class="overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-[11px] font-bold tracking-wide text-zinc-500 uppercase dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3">City</th>
                    <th class="px-4 py-3">Address</th>
                    <th class="px-4 py-3">Phones</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->showrooms as $showroom)
                    <tr wire:key="showroom-{{ $showroom->id }}">
                        <td class="px-4 py-3 font-medium">
                            {{ $showroom->city }}
                            @if ($showroom->is_hq)
                                <flux:badge color="blue" size="sm" inset="top bottom" class="ml-1">HQ</flux:badge>
                            @endif
                            <span class="block text-[11px] text-zinc-400">{{ $showroom->country }}</span>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">
                            {{ $showroom->address }}
                            @if ($showroom->pobox)
                                <span class="block text-[11px] text-zinc-400">{{ $showroom->pobox }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-[12px] text-zinc-500">{{ implode(' / ', $showroom->phones ?? []) }}</td>
                        <td class="px-4 py-3 tabular-nums text-zinc-500">{{ $showroom->sort_order }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $showroom->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash"
                                             wire:click="delete({{ $showroom->id }})"
                                             wire:confirm="Delete the {{ $showroom->city }} showroom?"
                                             class="text-red-500! hover:text-red-600!" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-zinc-400">No showrooms yet. Add your first location.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Showroom modal ── --}}
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

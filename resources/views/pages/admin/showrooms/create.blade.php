<?php

use App\Models\Showroom;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('New Showroom — Admin')] class extends Component
{
    public string $city = '';

    public string $country = 'Kenya';

    public string $address = '';

    public string $pobox = '';

    /** Comma-separated in the form; stored as a JSON array. */
    public string $phonesInput = '';

    public string $email = '';

    public string $latitude = '';

    public string $longitude = '';

    public bool $is_hq = false;

    public int $sort_order = 0;

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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_hq' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
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

        $showroom = Showroom::create([
            'city' => $data['city'],
            'country' => $data['country'],
            'address' => $data['address'],
            'pobox' => $data['pobox'] !== '' ? $data['pobox'] : null,
            'phones' => $phones,
            'email' => $data['email'] !== '' ? $data['email'] : null,
            'latitude' => $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            'longitude' => $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            'is_hq' => $data['is_hq'],
            'sort_order' => $data['sort_order'],
        ]);

        Flux::toast(heading: 'Showroom added', text: $showroom->city.' is now listed.', variant: 'success');
        $this->redirectRoute('admin.showrooms.edit', $showroom, navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.showrooms.index')" wire:navigate>Showrooms</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>New showroom</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">
        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">New showroom</flux:heading>
                <flux:subheading>A branch location with its address, map coordinates, and contact numbers.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.showrooms.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Add showroom</flux:button>
            </div>
        </div>

        <div class="mt-6">
            @include('partials.admin.showroom-form')
        </div>
    </form>
</div>

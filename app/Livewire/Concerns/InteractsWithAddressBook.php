<?php

namespace App\Livewire\Concerns;

use App\Models\Address;
use App\Models\DeliveryZone;
use App\Services\DeliveryResolver;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Shared "choose or add a delivery address" modal for storefront pages that
 * collect a delivery address (checkout, request a quote). Owns the address
 * form state, the select/create modal flow, and persistence. Pages render the
 * markup via the `partials.storefront.address-modal` partial.
 *
 * Page-specific side effects hang off the hook methods below
 * (`afterAddressSelected`, `afterAddressSaved`) — for example checkout
 * recomputes its delivery quote and resolves the county for reporting.
 */
trait InteractsWithAddressBook
{
    public ?int $selectedAddressId = null;

    public bool $showAddressModal = false;

    public string $addressModalMode = 'select';

    public string $label = 'Home';

    public string $name = '';

    public string $phone = '';

    public string $alternative_phone = '';

    public string $line1 = '';

    public string $delivery_instructions = '';

    public bool $is_default = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    /**
     * @return Collection<int, Address>
     */
    #[Computed]
    public function addresses(): Collection
    {
        if (auth()->guest()) {
            return collect();
        }

        return auth()->user()->addresses()->orderByDesc('is_default')->orderBy('created_at')->get();
    }

    #[Computed]
    public function selectedAddress(): ?Address
    {
        if (! $this->selectedAddressId) {
            return null;
        }

        return $this->addresses->firstWhere('id', $this->selectedAddressId);
    }

    /**
     * The zone the in-progress map pin falls into, for live feedback while
     * adding an address.
     */
    #[Computed]
    public function pinnedZone(): ?DeliveryZone
    {
        return app(DeliveryResolver::class)->resolveZone($this->latitude, $this->longitude);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function addressRules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternative_phone' => ['nullable', 'string', 'max:30'],
            'line1' => ['required', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function openAddressModal(string $mode = 'select'): void
    {
        $this->resetValidation();

        if ($mode === 'create' || $this->addresses->isEmpty()) {
            $this->prepareAddressForm();
            $this->addressModalMode = 'create';
        } else {
            $this->addressModalMode = 'select';
        }

        $this->showAddressModal = true;
    }

    public function startAddressCreate(): void
    {
        $this->resetValidation();
        $this->prepareAddressForm();
        $this->addressModalMode = 'create';
    }

    public function selectAddress(int $id): void
    {
        if ($this->addresses->contains('id', $id)) {
            $this->selectedAddressId = $id;
            unset($this->selectedAddress);
            $this->afterAddressSelected();
        }

        $this->showAddressModal = false;
    }

    public function saveAddress(): void
    {
        $data = $this->validate($this->addressRules());

        if ($data['is_default']) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        if (auth()->user()->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $data['delivery_zone_id'] = app(DeliveryResolver::class)->resolveZone($data['latitude'] ?? null, $data['longitude'] ?? null)?->id;

        $address = auth()->user()->addresses()->create($data);

        $this->selectedAddressId = $address->id;
        $this->showAddressModal = false;
        unset($this->addresses, $this->selectedAddress);

        $this->afterAddressSaved($address);

        Flux::toast(heading: 'Address added', text: 'Your delivery address has been saved.', variant: 'success');
    }

    private function prepareAddressForm(): void
    {
        $this->reset(['label', 'name', 'phone', 'alternative_phone', 'line1', 'delivery_instructions', 'is_default', 'latitude', 'longitude']);
        $this->label = 'Home';
    }

    /**
     * Hook for page-specific work after an existing address is selected.
     */
    protected function afterAddressSelected(): void
    {
        //
    }

    /**
     * Hook for page-specific work after a new address is persisted.
     */
    protected function afterAddressSaved(Address $address): void
    {
        //
    }
}

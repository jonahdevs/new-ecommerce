<?php

namespace App\Livewire\Forms;

use App\Models\Address;
use App\Models\Area;
use App\Models\County;
use Livewire\Form;

class CustomerAddressForm extends Form
{
    public ?Address $address = null;

    public string $first_name = '';
    public string $last_name = '';
    public string $phone_number = '';
    public ?string $alternative_phone_number = null;
    public ?string $county_id = null;
    public ?string $area_id = null;
    public string $address_text = '';
    public ?string $additional_information = null;
    public bool $is_default = false;

    //  Validation ─

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'regex:/^[0-9\s]{9,12}$/'],
            'alternative_phone_number' => ['nullable', 'string', 'regex:/^[0-9\s]{9,12}$/'],
            'county_id' => ['required', 'exists:counties,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'address_text' => ['required', 'string', 'max:500'],
            'additional_information' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Enter a valid phone number without the country code (e.g. 712 345 678).',
            'alternative_phone_number.regex' => 'Enter a valid phone number without the country code.',
            'county_id.required' => 'Please select a county.',
            'county_id.exists' => 'The selected county is invalid.',
            'area_id.exists' => 'The selected area is invalid.',
            'address_text.required' => 'Please enter a street address.',
        ];
    }

    //  Hydrate from existing address

    public function setAddress(Address $address): void
    {
        $this->address = $address;
        $this->first_name = $address->first_name;
        $this->last_name = $address->last_name;
        $this->phone_number = strip_phone_prefix($address->phone_number);
        $this->alternative_phone_number = strip_phone_prefix($address->alternative_phone_number);
        $this->county_id = $address->county_id;
        $this->area_id = $address->area_id ?? '';
        $this->address_text = $address->address;
        $this->additional_information = $address->additional_information;
        $this->is_default = $address->is_default;
    }

    //  Persist

    public function store(): Address
    {
        $this->validate();

        // First address is always default regardless of checkbox
        $isFirstAddress = !auth()->user()->addresses()->exists();
        $makeDefault = $isFirstAddress || $this->is_default;

        $address = auth()->user()->addresses()->create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => normalize_phone($this->phone_number),
            'alternative_phone_number' => normalize_phone($this->alternative_phone_number),
            'county_id' => $this->county_id,
            'area_id' => $this->area_id ?: null,
            'address' => $this->address_text,
            'additional_information' => $this->additional_information,
            'shipping_zone_id' => $this->resolveShippingZone(),
            'is_default' => $makeDefault,
        ]);

        if ($makeDefault) {
            $this->clearOtherDefaults($address);
        }

        return $address;
    }

    public function update(): Address
    {
        $this->validate();

        // If unchecking default but no other default exists, keep this one default
        $hasOtherDefault = auth()->user()
            ->addresses()
            ->where('id', '!=', $this->address->id)
            ->where('is_default', true)
            ->exists();

        $keepDefault = $this->is_default || !$hasOtherDefault;

        $this->address->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => normalize_phone($this->phone_number),
            'alternative_phone_number' => normalize_phone($this->alternative_phone_number),
            'county_id' => $this->county_id,
            'area_id' => $this->area_id ?: null,
            'address' => $this->address_text,
            'additional_information' => $this->additional_information,
            'shipping_zone_id' => $this->resolveShippingZone(),
            'is_default' => $keepDefault,
        ]);

        if ($keepDefault) {
            $this->clearOtherDefaults($this->address);
        }

        return $this->address->fresh();
    }

    //  Zone resolution

    /**
     * Resolve the shipping zone for this address.
     *
     * Priority:
     *   1. Area's zone override (if area is set and has one)
     *   2. County's zone (the default)
     *
     * This mirrors ShippingCalculator::resolveZone() exactly so that
     * the zone stored on the address always matches what checkout calculates.
     */
    protected function resolveShippingZone(): ?int
    {
        // Check area override first
        if ($this->area_id) {
            $areaZoneId = Area::where('id', $this->area_id)
                ->whereNotNull('shipping_zone_id')
                ->value('shipping_zone_id');

            if ($areaZoneId) {
                return $areaZoneId;
            }
        }

        // Fall back to county zone
        return County::where('id', $this->county_id)
            ->value('shipping_zone_id');
    }


    //  Default management

    protected function clearOtherDefaults(Address $exceptAddress): void
    {
        auth()->user()
            ->addresses()
            ->where('id', '!=', $exceptAddress->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}

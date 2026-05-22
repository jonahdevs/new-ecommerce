<?php

namespace App\Livewire\Forms;

use App\Models\Address;
use App\Models\County;
use App\Models\ShippingZone;
use App\Models\SubCounty;
use App\Models\Town;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CustomerAddressForm extends Form
{
    public ?Address $address = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $phone_number = '';

    public ?string $alternative_phone_number = null;

    public ?string $county_id = null;

    public ?int $sub_county_id = null;

    public ?int $town_id = null;

    public string $address_text = '';

    public ?string $additional_information = null;

    public bool $is_default = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    //  Validation

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'regex:/^[0-9]{3}\s?[0-9]{3}\s?[0-9]{3}$/'],
            'alternative_phone_number' => ['nullable', 'string', 'regex:/^[0-9]{3}\s?[0-9]{3}\s?[0-9]{3}$/'],
            'county_id' => [
                'required',
                'exists:counties,id',
                function ($attribute, $value, $fail) {
                    $zoneId = $this->resolveShippingZone();

                    if (! $zoneId) {
                        $fail('Delivery is not available in this county. Please select a different location or contact support.');

                        return;
                    }

                    $zone = ShippingZone::find($zoneId);

                    if (! $zone || $zone->status->value !== 'active') {
                        $fail('Delivery is temporarily unavailable in this area. Please try again later or contact support.');
                    }
                },
            ],
            'sub_county_id' => ['nullable', 'exists:sub_counties,id'],
            'town_id' => ['nullable', 'exists:towns,id'],
            'address_text' => [
                Rule::requiredIf(fn () => ! $this->latitude || ! $this->longitude),
                'nullable',
                'string',
                'max:500',
            ],
            'additional_information' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Enter a valid phone number without the country code (e.g. 712 345 678).',
            'alternative_phone_number.regex' => 'Enter a valid phone number without the country code.',
            'county_id.required' => 'Please select a county.',
            'county_id.exists' => 'The selected county is invalid.',
            'address_text.required' => 'Please enter a street address or drop a pin on the map.',
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
        $this->county_id = (string) $address->county_id;
        $this->sub_county_id = $address->sub_county_id;
        $this->town_id = $address->town_id;
        $this->address_text = $address->address;
        $this->additional_information = $address->additional_information;
        $this->is_default = $address->is_default;
        $this->latitude = $address->latitude ? (float) $address->latitude : null;
        $this->longitude = $address->longitude ? (float) $address->longitude : null;
    }

    //  Persist

    public function store(): Address
    {
        $this->validate();

        $isFirstAddress = ! auth()->user()->addresses()->exists();
        $makeDefault = $isFirstAddress || $this->is_default;

        $address = auth()->user()->addresses()->create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => normalize_phone($this->phone_number),
            'alternative_phone_number' => normalize_phone($this->alternative_phone_number),
            'county_id' => $this->county_id,
            'sub_county_id' => $this->sub_county_id,
            'town_id' => $this->town_id,
            'address' => $this->address_text,
            'additional_information' => $this->additional_information,
            'shipping_zone_id' => $this->resolveShippingZone(),
            'is_default' => $makeDefault,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ]);

        if ($makeDefault) {
            $this->clearOtherDefaults($address);
        }

        return $address;
    }

    public function update(): Address
    {
        $this->validate();

        $hasOtherDefault = auth()->user()
            ->addresses()
            ->where('id', '!=', $this->address->id)
            ->where('is_default', true)
            ->exists();

        $keepDefault = $this->is_default || ! $hasOtherDefault;

        $this->address->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => normalize_phone($this->phone_number),
            'alternative_phone_number' => normalize_phone($this->alternative_phone_number),
            'county_id' => $this->county_id,
            'sub_county_id' => $this->sub_county_id,
            'town_id' => $this->town_id,
            'address' => $this->address_text,
            'additional_information' => $this->additional_information,
            'shipping_zone_id' => $this->resolveShippingZone(),
            'is_default' => $keepDefault,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ]);

        if ($keepDefault) {
            $this->clearOtherDefaults($this->address);
        }

        return $this->address->fresh();
    }

    //  Zone resolution

    /**
     * Priority: town zone → sub-county zone → county zone.
     * Mirrors Address::resolveShippingZone() exactly.
     */
    protected function resolveShippingZone(): ?int
    {
        if ($this->town_id) {
            $town = Town::with('shippingZone', 'subCounty.shippingZone', 'county.shippingZone')->find($this->town_id);

            if ($town) {
                return $town->shipping_zone_id
                    ?? $town->subCounty?->shipping_zone_id
                    ?? $town->county?->shipping_zone_id;
            }
        }

        if ($this->sub_county_id) {
            $subCounty = SubCounty::with('county')->find($this->sub_county_id);

            if ($subCounty) {
                return $subCounty->shipping_zone_id
                    ?? $subCounty->county?->shipping_zone_id;
            }
        }

        return County::where('id', $this->county_id)->value('shipping_zone_id');
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

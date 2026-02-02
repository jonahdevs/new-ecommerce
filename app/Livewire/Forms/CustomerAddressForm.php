<?php

namespace App\Livewire\Forms;

use App\Models\Address;
use App\Models\Area;
use App\Models\County;
use Livewire\Form;

class CustomerAddressForm extends Form
{
    public ?Address $address;
    public string $first_name = '';
    public string $last_name = '';
    public string $phone_number = '';
    public ?string $alternative_phone_number = null;
    public ?int $county_id = null;
    public ?int $area_id = null;
    public string $address_text = '';
    public ?string $additional_information = null;
    public bool $is_default = false;

    public function rules()
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255'],
            'alternative_phone_number' => ['nullable', 'string', 'max:255'],
            'county_id' => ['required', 'exists:counties,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'address_text' => ['required', 'string'],
            'additional_information' => ['nullable', 'string'],
            'is_default' => ['boolean'],
        ];
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;

        $this->first_name = $address->first_name;
        $this->last_name = $address->last_name;
        $this->phone_number = $address->phone_number;
        $this->alternative_phone_number = $address->alternative_phone_number;
        $this->county_id = $address->county_id;
        $this->area_id = $address->area_id;
        $this->address_text = $address->address;
        $this->additional_information = $address->additional_information;
        $this->is_default = $address->is_default;
    }

    public function store()
    {
        \Log::info($this->all());
        $this->validate();

        if (!$this->is_default && !auth()->user()->addresses()->where('is_default', true)->exists()) {
            $this->is_default = true;
        }


        $address = auth()->user()->addresses()->create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => $this->phone_number,
            'alternative_phone_number' => $this->alternative_phone_number,
            'county_id' => $this->county_id,
            'area_id' => $this->area_id,
            'address' => $this->address_text,
            'additional_information' => $this->additional_information,
            'shipping_zone_id' => $this->determineShippingZone(),
            'is_default' => $this->is_default,
        ]);

        if ($this->is_default) {
            $this->unsetOtherDefaultAddresses($address);
        }

        return $address;
    }

    public function update()
    {
        $this->validate();

        $this->address->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => $this->phone_number,
            'alternative_phone_number' => $this->alternative_phone_number,
            'county_id' => $this->county_id,
            'area_id' => $this->area_id,
            'address' => $this->address_text,
            'additional_information' => $this->additional_information,
            'shipping_zone_id' => $this->determineShippingZone(),
            'is_default' => $this->is_default,
        ]);

        if ($this->is_default) {
            $this->unsetOtherDefaultAddresses($this->address);
        }

        return $this->address;
    }

    protected function determineShippingZone()
    {
        if ($this->area_id) {
            $areaShippingZone = Area::where('id', $this->area_id)
                ->value('shipping_zone_id');

            if ($areaShippingZone) {
                return $areaShippingZone;
            }
        }

        return County::where('id', $this->county_id)
            ->value('shipping_zone_id');
    }

    protected function unsetOtherDefaultAddresses(Address $exceptAddress)
    {
        auth()->user()
            ->addresses()
            ->where('id', '!=', $exceptAddress->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}

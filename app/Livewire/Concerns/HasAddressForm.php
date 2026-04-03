<?php

namespace App\Livewire\Concerns;

use App\Models\Area;
use App\Models\County;
use Livewire\Attributes\Computed;

/**
 * Shared logic for all address create/edit pages (checkout + address-book).
 * Include this trait in any Livewire component that owns a CustomerAddressForm.
 */
trait HasAddressForm
{
    #[Computed]
    public function counties()
    {
        return County::orderBy('name')->get();
    }

    #[Computed]
    public function areas()
    {
        if (! $this->form->county_id) {
            return collect();
        }

        return Area::where('county_id', $this->form->county_id)->orderBy('name')->get();
    }

    #[Computed]
    public function hasDefaultAddress(): bool
    {
        return auth()->user()->addresses()->where('is_default', true)->exists();
    }

    #[Computed]
    public function mapState(): array
    {
        $county = $this->form->county_id
            ? County::with('boundary')->find($this->form->county_id)
            : null;

        $area = $this->form->area_id
            ? Area::find($this->form->area_id)
            : null;

        return [
            'pin' => [
                'lat' => $this->form->latitude,
                'lng' => $this->form->longitude,
            ],
            'center' => [
                'lat' => $area?->lat_center ?? ($county?->lat_center ?? -1.2921),
                'lng' => $area?->lng_center ?? ($county?->lng_center ?? 36.8219),
            ],
            'countyName' => $county?->name,
            'boundaryGeojson' => $county?->boundary?->geojson ?? null,
        ];
    }

    public function getMapState(): array
    {
        return $this->mapState();
    }

    public function updatedFormCountyId(): void
    {
        $this->form->area_id = null;
    }

    public function updatedFormAreaId(): void
    {
        // Triggers mapState recompute — JS picks it up via $wire
    }
}

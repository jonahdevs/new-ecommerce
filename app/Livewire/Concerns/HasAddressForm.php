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

    public function resolveCountyFromName(string $rawName): ?array
    {
        $rawName = trim($rawName);

        if (! $rawName) {
            return null;
        }

        // Strip Nominatim admin-unit suffixes that appear in Kenya:
        // "Nairobi City County" → "Nairobi", "Westlands Ward" → "Westlands" (then fails to match a county, triggering fallback)
        $name = trim(preg_replace('/\s+(city\s+)?(county|ward|sub-?county|division|location|sub-?location)\s*$/i', '', $rawName));

        $county = County::whereRaw('LOWER(name) = ?', [strtolower($name)])->first()
            ?? County::where(function ($q) use ($name) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($name).'%'])
                    ->orWhereRaw('LOWER(?) LIKE CONCAT(\'%\', LOWER(name), \'%\')', [strtolower($name)]);
            })->first();

        if (! $county) {
            return null;
        }

        $this->form->county_id = (string) $county->id;
        $this->form->area_id = null;

        return ['id' => $county->id, 'name' => $county->name];
    }

    public function resolveAreaFromName(string $rawName): ?array
    {
        $rawName = trim($rawName);

        if (! $rawName || ! $this->form->county_id) {
            return null;
        }

        $area = Area::where('county_id', $this->form->county_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($rawName).'%'])
            ->first();

        if (! $area) {
            return null;
        }

        $this->form->area_id = (string) $area->id;

        return ['id' => $area->id, 'name' => $area->name];
    }
}

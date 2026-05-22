<?php

namespace App\Livewire\Concerns;

use App\Models\County;
use App\Services\SubCountyResolver;
use App\Services\TownResolver;
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

        return [
            'pin' => [
                'lat' => $this->form->latitude,
                'lng' => $this->form->longitude,
            ],
            'center' => [
                'lat' => $county?->lat_center ?? -1.2921,
                'lng' => $county?->lng_center ?? 36.8219,
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
        $this->form->latitude = null;
        $this->form->longitude = null;
        $this->form->sub_county_id = null;
        $this->form->town_id = null;
    }

    /**
     * Called by the map JS whenever the customer moves their pin.
     * Runs point-in-polygon to silently resolve the sub-county and zone.
     * Returns county resolution state so JS can update Alpine without a second server call.
     *
     * @return array{countyResolved: bool, countyName: string}
     */
    public function pinDropped(float $lat, float $lng): array
    {
        $this->form->latitude = $lat;
        $this->form->longitude = $lng;

        $subCounty = app(SubCountyResolver::class)->resolve($lat, $lng);

        if ($subCounty) {
            $this->form->sub_county_id = $subCounty->id;
            $this->form->county_id = (string) $subCounty->county_id;

            $town = app(TownResolver::class)->resolve($lat, $lng);
            $this->form->town_id = $town?->id;

            return ['countyResolved' => true, 'countyName' => $subCounty->county->name ?? ''];
        }

        $this->form->sub_county_id = null;
        $this->form->town_id = null;

        return ['countyResolved' => false, 'countyName' => ''];
    }

    /**
     * Called by the map JS after Nominatim geocoding resolves a county name.
     * Strips Nominatim admin-unit suffixes before matching.
     */
    public function resolveCountyFromName(string $rawName): ?array
    {
        $rawName = trim($rawName);

        if (! $rawName) {
            return null;
        }

        $name = trim(preg_replace(
            '/\s+(city\s+)?(county|ward|sub-?county|division|location|sub-?location)\s*$/i',
            '',
            $rawName
        ));

        $county = County::whereRaw('LOWER(name) = ?', [strtolower($name)])->first()
            ?? County::where(function ($q) use ($name) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($name).'%'])
                    ->orWhereRaw('LOWER(?) LIKE CONCAT(\'%\', LOWER(name), \'%\')', [strtolower($name)]);
            })->first();

        if (! $county) {
            return null;
        }

        $this->form->county_id = (string) $county->id;
        $this->form->sub_county_id = null;

        return ['id' => $county->id, 'name' => $county->name];
    }
}

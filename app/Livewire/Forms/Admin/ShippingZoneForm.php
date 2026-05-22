<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\ShippingZoneStatus;
use App\Models\ShippingZone;
use Livewire\Form;

class ShippingZoneForm extends Form
{
    public ?ShippingZone $zone = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public string $status = 'active';

    public bool $is_delivery_available = false;

    /**
     * UI-only field. Picking a preset prefills the four real fields so admins
     * don't have to remember what each tier means. Not persisted.
     */
    public string $tier = '';

    /**
     * Tier templates. Stay in code (not DB) because they're opinionated defaults,
     * not schema. Admins can edit any field after picking a preset.
     *
     * @var array<string, array{name: string, code: string, description: string, is_delivery_available: bool}>
     */
    public const TIER_PRESETS = [
        'within_nairobi' => [
            'name' => 'Within Nairobi',
            'code' => 'within_nairobi',
            'description' => 'Nairobi County and nearby satellite towns (Syokimau, Mlolongo, Athi River, Kitengela, Rongai, Kiambu Town, Thika Town, etc.). Base rate tier.',
            'is_delivery_available' => true,
        ],
        'upcountry' => [
            'name' => 'Upcountry',
            'code' => 'upcountry',
            'description' => 'Rest of Kenya outside the Nairobi ring. Door delivery at a higher rate.',
            'is_delivery_available' => true,
        ],
        'pus_only' => [
            'name' => 'PUS Only',
            'code' => 'pus_only',
            'description' => 'Pickup-station collection only. No door delivery.',
            'is_delivery_available' => false,
        ],
    ];

    public function rules(): array
    {
        $uniqueCode = 'nullable|string|max:50|alpha_dash|unique:shipping_zones,code';

        if ($this->zone) {
            $uniqueCode .= ",{$this->zone->id}";
        }

        return [
            'name' => 'required|string|max:100',
            'code' => $uniqueCode,
            'description' => 'nullable|string|max:500',
            'status' => 'required|string|in:'.implode(',', array_column(ShippingZoneStatus::cases(), 'value')),
        ];
    }

    public function messages(): array
    {
        return [
            'code.alpha_dash' => 'Code may only contain letters, numbers, dashes and underscores.',
            'code.unique' => 'This code is already taken by another zone.',
        ];
    }

    /**
     * Apply a tier template. Called from the view when the admin picks a preset.
     * Safe to call repeatedly — overwrites the four real fields.
     */
    public function applyTier(string $tier): void
    {
        $this->tier = $tier;

        if (! isset(self::TIER_PRESETS[$tier])) {
            return;
        }

        $preset = self::TIER_PRESETS[$tier];
        $this->name = $preset['name'];
        $this->code = $preset['code'];
        $this->description = $preset['description'];
        $this->is_delivery_available = $preset['is_delivery_available'];
    }

    public function setZone(ShippingZone $zone): void
    {
        $this->zone = $zone;
        $this->name = $zone->name;
        $this->code = $zone->code ?? '';
        $this->description = $zone->description ?? '';
        $this->status = $zone->status instanceof ShippingZoneStatus
            ? $zone->status->value
            : $zone->status;
        $this->is_delivery_available = $zone->is_delivery_available;
        $this->tier = $this->detectTier($zone);
    }

    /**
     * Reverse-lookup which preset (if any) a zone matches, by code.
     * Used when editing — selects the preset radio if applicable.
     */
    private function detectTier(ShippingZone $zone): string
    {
        foreach (self::TIER_PRESETS as $key => $preset) {
            if ($zone->code === $preset['code']) {
                return $key;
            }
        }

        return 'custom';
    }

    public function store(): ShippingZone
    {
        $this->validate();

        return ShippingZone::create([
            'name' => $this->name,
            'code' => $this->code ?: null,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'is_delivery_available' => $this->is_delivery_available,
        ]);
    }

    public function update(): void
    {
        $this->validate();

        $this->zone->update([
            'name' => $this->name,
            'code' => $this->code ?: null,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'is_delivery_available' => $this->is_delivery_available,
        ]);
    }
}

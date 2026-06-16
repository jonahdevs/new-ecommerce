<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Logistics\DTOs\TrackingResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'shipping_method_id', 'carrier_id', 'warehouse_id',
    'tracking_number', 'tracking_url', 'status',
    'carrier_booking_ref', 'carrier_payload',
    'estimated_delivery_at', 'booked_at', 'picked_up_at',
    'delivered_at', 'failed_at', 'notes',
])]
class Shipment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'carrier_payload' => 'array',
            'estimated_delivery_at' => 'datetime',
            'booked_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // ==================================================
    // HELPERS
    // ==================================================

    public function transitionTo(ShipmentStatus $status): void
    {
        $timestamps = match ($status) {
            ShipmentStatus::BOOKED => ['booked_at' => now()],
            ShipmentStatus::PICKED_UP => ['picked_up_at' => now()],
            ShipmentStatus::DELIVERED => ['delivered_at' => now()],
            ShipmentStatus::FAILED => ['failed_at' => now()],
            default => [],
        };

        $this->update(array_merge(['status' => $status], $timestamps));
    }

    public function isPickup(): bool
    {
        return $this->warehouse_id !== null;
    }

    public function refreshFromCarrier(): TrackingResult
    {
        if ($this->isPickup()) {
            return TrackingResult::failed('Pickup shipments do not use carrier tracking.');
        }

        if (! $this->carrier) {
            return TrackingResult::failed('No carrier assigned to this shipment.');
        }

        $result = $this->carrier->logisticsDriver()->track($this);

        if ($result->success && $result->status) {
            $this->transitionTo($result->status);
        }

        return $result;
    }
}

<?php

// ============================================================
//  1. REGISTER THE SERVICE — add to AppServiceProvider.php
//     (or create a dedicated ShippingServiceProvider)
// ============================================================

// In app/Providers/AppServiceProvider.php → register() method:

use App\Services\Shipping\ShippingCalculator;
use App\Services\Shipping\Engines\FlatRateEngine;
use App\Services\Shipping\Engines\PusEngine;

$this->app->singleton(ShippingCalculator::class, function () {
    return new ShippingCalculator(
        flatEngine: new FlatRateEngine(),
        pusEngine:  new PusEngine(),
    );
});


// ============================================================
//  2. USAGE EXAMPLES
// ============================================================

//  Basic — get all options for a county 

$calculator = app(ShippingCalculator::class);

$options = $calculator->calculate(
    countyId:    16,     // Machakos
    areaId:      null,
    weightKg:    3.5,
    orderAmount: 4500,
);

// $options is a Collection<ShippingOption>, sorted cheapest first.
// Each option:
//   $option->methodName       "Standard Delivery"
//   $option->cost             600.0
//   $option->formattedCost()  "KES 600"
//   $option->deliveryWindow() "2–4 days"
//   $option->costBreakdown    [...] — stored on DeliveryOrder
//   $option->isPus()          false


//  With area override 

$options = $calculator->calculate(
    countyId:    16,     // Machakos
    areaId:      42,     // Mlolongo — might have zone override
    weightKg:    3.5,
    orderAmount: 4500,
);


//  PUS — recalculate after station selection 

$pusOption = $options->firstWhere('methodType', 'pus');

// Customer picks a station — update the cost
if ($pusOption) {
    $updatedOption = $calculator->recalculateForStation(
        option:    $pusOption,
        stationId: 1,        // chosen station ID
        weightKg:  3.5,
    );

    // $updatedOption now has station-specific surcharge in cost_breakdown
}


//  In a Livewire checkout component 

// public function updatedCountyId(): void
// {
//     $this->areaId    = null;
//     $this->options   = app(ShippingCalculator::class)
//         ->calculate($this->countyId, null, $this->cartWeight, $this->cartTotal)
//         ->toArray();
// }

// public function updatedAreaId(): void
// {
//     $this->options   = app(ShippingCalculator::class)
//         ->calculate($this->countyId, $this->areaId, $this->cartWeight, $this->cartTotal)
//         ->toArray();
// }

// public function updatedSelectedStationId(): void
// {
//     // Recalculate PUS option only
//     $pusOption = collect($this->options)->firstWhere('method_type', 'pus');
//     if ($pusOption) {
//         $option = new ShippingOption(...$pusOption);
//         $updated = app(ShippingCalculator::class)
//             ->recalculateForStation($option, $this->selectedStationId, $this->cartWeight);
//         // Replace pus option in $this->options
//     }
// }


//  Storing on DeliveryOrder when order is placed 

// $selectedOption = ShippingOption from session/request

// DeliveryOrder::create([
//     'order_id'              => $order->id,
//     'logistics_provider_id' => $method->logistics_provider_id,
//     'shipping_method_id'    => $selectedOption->methodId,
//     'shipping_zone_id'      => $selectedOption->shippingZoneId,
//     'shipping_rate_id'      => $selectedOption->shippingRateId,
//     'pickup_station_id'     => $selectedStationId ?? null,
//     'shipping_cost'         => $selectedOption->cost,
//     'cost_breakdown'        => $selectedOption->costBreakdown,
//     'package_weight_kg'     => $cartWeight,
//     'estimated_delivery_at' => now()->addDays($selectedOption->estimatedDaysMax),
//     'status'                => DeliveryOrderStatus::Pending->value,
//     'is_return'             => false,
// ]);


//  Free shipping example 

// Activate the free shipping rule in admin, then:
$options = $calculator->calculate(
    countyId:    47,      // Nairobi
    areaId:      null,
    weightKg:    2.0,     // under 10kg max_weight on the rule
    orderAmount: 6000,    // above 5000 min_order_amount
);

// Standard Delivery option will have:
//   cost = 0.0
//   isFree() = true
//   costBreakdown['free_shipping'] = true
//   costBreakdown['discount'] = 400  (the waived amount)

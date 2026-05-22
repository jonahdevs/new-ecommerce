# Shipping System — Developer Reference

This document covers the full shipping architecture for the Sheffield Africa e-commerce platform. It explains every table, every field, every configuration decision, and the known edge cases uncovered during development. Read this before touching anything in the shipping module.

---

## Files

| File                         | Responsibility                                                                |
| ---------------------------- | ----------------------------------------------------------------------------- |
| `ShippingCalculator.php`     | Single entry point — resolves all shipping options for a given address + cart |
| `ShippingOption.php`         | Value object returned per available shipping method                           |
| `Engines/FlatRateEngine.php` | Weight bracket × zone pricing (standard delivery)                             |
| `Engines/PusEngine.php`      | Pickup station pricing (line haul + surcharge)                                |

**Distance engine** (`distance` type) is defined in the schema but not yet implemented. Vehicle rates exist in the DB for future use.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Database Tables](#database-tables)
3. [Pricing Engines](#pricing-engines)
4. [ShippingCalculator](#shippingcalculator)
5. [Checkout Flow](#checkout-flow)
6. [Delivery Windows](#delivery-windows)
7. [Pickup Station Fallback](#pickup-station-fallback)
8. [Expanding to New Zones](#expanding-to-new-zones)
9. [Free Shipping Rules](#free-shipping-rules)
10. [Known Gotchas](#known-gotchas)

---

## Architecture Overview

```
Customer address
      ↓
Zone resolution (county → area override)
      ↓
ShippingCalculator::calculate()
      ↓
┌─────────────┬──────────────┐
│ FlatEngine  │  PusEngine   │
│ (standard)  │  (pickup)    │
└─────────────┴──────────────┘
      ↓
Collection<ShippingOption>
      ↓
Blade / Livewire checkout UI
```

There are three pricing engine types defined in `shipping_methods.type`:

| Type       | Engine         | Description                           |
| ---------- | -------------- | ------------------------------------- |
| `flat`     | FlatRateEngine | Weight bracket → fixed price per zone |
| `pus`      | PusEngine      | Line haul + pickup station surcharge  |
| `distance` | (future)       | Vehicle + km calculation              |

Currently only `flat` and `pus` are active.

---

## Database Tables

### `logistics_providers`

Who fulfills the delivery. Start with one internal row (Sheffield Africa). Add external providers (Sendy, DHL) as new rows — nothing else in the schema changes.

| Field    | Type   | Notes                                                               |
| -------- | ------ | ------------------------------------------------------------------- |
| `name`   | string | Display name                                                        |
| `code`   | string | Unique slug. e.g. `sheffield`                                       |
| `type`   | enum   | `internal` or `external`                                            |
| `status` | string | Cast: `LogisticsProviderStatus` — `active`, `inactive`, `suspended` |

`suspended` means there is an operational or billing issue. The provider is still referenced on historical orders but unavailable at checkout.

---

### `shipping_zones`

Geographic rate brackets. Every county and area maps to a zone. The zone determines which rate rows apply at checkout.

| Field                   | Type    | Notes                                                                                                                                                                                                   |
| ----------------------- | ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `name`                  | string  | e.g. `Nairobi`, `Upcountry`                                                                                                                                                                             |
| `code`                  | string  | Unique slug. e.g. `nairobi`                                                                                                                                                                             |
| `status`                | string  | Cast: `ShippingZoneStatus` — `active`, `inactive`                                                                                                                                                       |
| `is_delivery_available` | boolean | **Critical flag.** Controls whether flat/doorstep delivery is offered at checkout. Set to `false` for zones not yet open. Does NOT block PUS — see [Pickup Station Fallback](#pickup-station-fallback). |

**When expanding to a new region:** flip `is_delivery_available` to `true` and ensure shipping rates exist for that zone. Nothing else needs to change.

---

### `counties`

Kenya's 47 counties. Each belongs to a shipping zone.

| Field              | Type   | Notes                                                              |
| ------------------ | ------ | ------------------------------------------------------------------ |
| `code`             | string | County number from Kenya National Bureau of Statistics. e.g. `001` |
| `shipping_zone_id` | FK     | Determines the rate bracket for this county                        |

Counties are seeded from `database/seeders/data/counties.json`. Do not edit county zone assignments directly in the DB — update the JSON and re-seed.

---

### `areas`

Towns, suburbs, and estates within a county. Used for granular zone overrides.

| Field              | Type    | Notes                                                                                                                                                                   |
| ------------------ | ------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `county_id`        | FK      | Parent county                                                                                                                                                           |
| `shipping_zone_id` | FK/null | **Leave null unless this area ships differently to its county.** When null, the area inherits its county's zone. Set explicitly only for border towns or special cases. |

**Zone resolution priority:**

1. Area's own `shipping_zone_id` (if set)
2. Parent county's `shipping_zone_id`

---

### `shipping_methods`

The delivery products shown at checkout. Provider-aware.

| Field                   | Type    | Notes                                                             |
| ----------------------- | ------- | ----------------------------------------------------------------- |
| `logistics_provider_id` | FK      | Which provider fulfills this method                               |
| `code`                  | string  | Unique slug. e.g. `standard`, `pickup`                            |
| `type`                  | enum    | `flat`, `pus`, or `distance` — selects the pricing engine         |
| `supports_returns`      | boolean | Whether reverse logistics applies to this method                  |
| `delivery_time_unit`    | enum    | `hours` or `days` — affects how `estimated_days_*` is displayed   |
| `sort_order`            | integer | Controls display order at checkout                                |
| `status`                | string  | Cast: `ShippingMethodStatus` — `active`, `inactive`, `deprecated` |

`deprecated` means the method is no longer selectable but is still referenced on historical orders. Never delete a deprecated method.

---

### `shipping_rates`

Weight bracket × zone pricing. One row per tier per zone per method.

| Field                | Type    | Notes                                                                                                                              |
| -------------------- | ------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| `shipping_zone_id`   | FK      | The zone this rate applies to                                                                                                      |
| `shipping_method_id` | FK      | The method this rate belongs to                                                                                                    |
| `min_weight`         | decimal | Lower bound of the weight bracket (inclusive)                                                                                      |
| `max_weight`         | decimal | Upper bound (inclusive). `null` means no upper limit (XL tier)                                                                     |
| `weight_label`       | string  | Human-readable label shown at checkout and on invoices. e.g. `Small (0–5 Kg)`. **Keep min/max consistent with the actual values.** |
| `price`              | decimal | Cost in KES. Currently `0` — absorbed into product pricing                                                                         |
| `estimated_days_min` | integer | Works in hours or days depending on `shipping_methods.delivery_time_unit`                                                          |
| `estimated_days_max` | integer | Same unit as above                                                                                                                 |
| `status`             | string  | Cast: `ShippingRateStatus` — `active`, `inactive`, `expired`                                                                       |

`expired` means superseded by a newer rate. Keep for historical order reference — do not delete.

**Weight tiers (current):**

| Tier   | min_weight | max_weight | weight_label     |
| ------ | ---------- | ---------- | ---------------- |
| Small  | 0          | 5          | Small (0–5 Kg)   |
| Medium | 5.1        | 20         | Medium (5–20 Kg) |
| Large  | 20.1       | 60         | Large (20–60 Kg) |
| XL     | 60.1       | null       | XL (60 Kg+)      |

**Important:** Weight affects the cost bracket only. It does not affect the delivery window. All tiers within the same method and zone share the same `estimated_days_min` / `estimated_days_max`. See [Delivery Windows](#delivery-windows).

---

### `vehicle_rates`

Powers the On-Demand (`distance`) engine. Not yet active. Formula:

```
Total = base_rate + max(0, actual_km − base_km) × extra_km_rate
```

`actual_km` comes from Google Maps Distance Matrix API at checkout.

| Field           | Type    | Notes                                 |
| --------------- | ------- | ------------------------------------- |
| `vehicle_type`  | string  | Cast: `VehicleType` enum              |
| `base_rate`     | decimal | Fixed charge up to `base_km`          |
| `base_km`       | integer | Free distance included in base rate   |
| `extra_km_rate` | decimal | Per-km charge beyond `base_km`        |
| `max_weight_kg` | decimal | Maximum cargo weight for this vehicle |

---

### `pickup_stations`

Physical collection points for the PUS model.

| Field                   | Type    | Notes                                                                                                                                                                              |
| ----------------------- | ------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `logistics_provider_id` | FK      | Which provider operates this station                                                                                                                                               |
| `code`                  | string  | Unique slug. e.g. `nbo-syokimau`                                                                                                                                                   |
| `county_id`             | FK      | County the station is in. Used for zone resolution and nearby sorting                                                                                                              |
| `sub_county_id`         | FK/null | Optional — narrows the location within the county to a specific sub-county                                                                                                         |
| `holding_days`          | integer | Days before uncollected parcels are returned. Default `7`                                                                                                                          |
| `is_primary`            | boolean | **Always offer this station at checkout regardless of the customer's zone.** Set `true` on your headquarters/main station. Every deployment must have exactly one primary station. |
| `status`                | string  | Cast: `PickupStationStatus` — `active`, `inactive`, `temporarily_closed`                                                                                                           |

`temporarily_closed` means the station is not routing parcels but still appears on historical orders. Parcels are not sent here until status returns to `active`.

---

### `shipping_rate_addons`

Station surcharges stacked on top of the PUS line haul.

```
Total PUS cost = shipping_rates.price (line haul)
              + shipping_rate_addons.addon_amount (surcharge)
```

| Field               | Type    | Notes                                                                                          |
| ------------------- | ------- | ---------------------------------------------------------------------------------------------- |
| `shipping_rate_id`  | FK      | The base rate this addon applies to                                                            |
| `addon_type`        | string  | Cast: `AddonType` — `pus`, `fuel_surcharge`, `remote_area`. Extensible without schema changes. |
| `pickup_station_id` | FK/null | `null` = applies to all stations. Set to target a specific station.                            |
| `addon_amount`      | decimal | Currently `0`. Update this column when introducing paid surcharges.                            |

**Addon resolution:** Global addons (`pickup_station_id = null`) always apply. Station-specific addons stack on top. Both are summed in `PusEngine::resolveSurcharge()`.

---

### `free_shipping_rules`

Promotional thresholds. Scoped to a zone and/or method (`null` = all).

| Field              | Type      | Notes                                                                         |
| ------------------ | --------- | ----------------------------------------------------------------------------- |
| `min_order_amount` | decimal   | Minimum basket value to qualify. `0` = every order qualifies                  |
| `max_weight`       | decimal   | Optional cap — exclude heavy shipments from the offer                         |
| `starts_at`        | timestamp | `null` = active immediately                                                   |
| `ends_at`          | timestamp | `null` = never expires                                                        |
| `status`           | string    | Cast: `FreeShippingRuleStatus` — `scheduled`, `active`, `expired`, `inactive` |

Lifecycle is driven by `starts_at` / `ends_at`. A scheduled job transitions status automatically:

- `scheduled` → `active` when `starts_at` is reached
- `active` → `expired` when `ends_at` is passed

---

### `addresses`

Customer delivery addresses. `shipping_zone_id` is resolved at save time from the area override or county zone — do not let this go null.

| Field              | Type    | Notes                                                                          |
| ------------------ | ------- | ------------------------------------------------------------------------------ |
| `shipping_zone_id` | FK      | Resolved at save time. Never re-derive at checkout — read this field directly. |
| `is_default`       | boolean | First address created is always set as default. No default = no addresses.     |

**Address resolution at checkout (in order):**

1. Address ID stored in `CheckoutSession`
2. User's default address (`is_default = true`)
3. If neither exists → redirect to `checkout.addresses.create`

---

### `delivery_orders`

Audit trail for every delivery. `cost_breakdown` JSON is the source of truth for invoicing. FK references exist for querying and reporting only.

| Field                    | Type      | Notes                                                                            |
| ------------------------ | --------- | -------------------------------------------------------------------------------- |
| `cost_breakdown`         | json      | Full itemised breakdown. Never derive cost from FK references — read this field. |
| `shipping_rate_id`       | FK/null   | Set for `flat` and `pus` methods                                                 |
| `vehicle_rate_id`        | FK/null   | Set for `distance` method                                                        |
| `pickup_station_id`      | FK/null   | Set for `pus` method                                                             |
| `is_return`              | boolean   | `true` = reverse logistics. Same rate engine fires for returns                   |
| `provider_reference`     | string    | External provider's job/tracking reference                                       |
| `collection_deadline_at` | timestamp | PUS only. `delivered_at + pickup_stations.holding_days`                          |

**DeliveryOrderStatus flow:**

```
pending → picked_up → in_transit → out_for_delivery → delivered
                                                    ↘ failed → returning → returned
at_station → collected
cancelled (terminal, before pickup only)
```

---

## Pricing Engines

### FlatRateEngine

Resolves a `shipping_rates` row by matching zone + method + weight bracket. Checks for active free shipping rules and zeroes the cost if one matches.

### PusEngine

**Critical behaviour:** PUS rates are always resolved against the primary station's zone (Nairobi), not the customer's zone. The customer travels to the station — their location does not affect the pickup cost.

```php
// PusEngine::resolvePusZone()
// Gets the zone from the primary station's county, not the customer's address.
$primaryStation = PickupStation::where('is_primary', true)
    ->where('status', 'active')
    ->with('county.shippingZone')
    ->first();
```

Station resolution always includes primary stations regardless of the customer's county:

```php
// PusEngine::resolveStations()
PickupStation::where('status', 'active')
    ->where(function ($query) use ($countyId) {
        $query->where('is_primary', true);
        if ($countyId) {
            $query->orWhere('county_id', $countyId);
        }
    })
    ->orderByRaw(
        $countyId
            ? 'CASE WHEN county_id = ? THEN 0 ELSE 1 END, name ASC'
            : 'name ASC',
        $countyId ? [$countyId] : []
    )
    ->get();
```

Stations in the customer's county sort first (nearby), primary station sorts after if it's in a different county.

---

## ShippingCalculator

Single entry point for all shipping option resolution.

```php
$options = app(ShippingCalculator::class)->calculate(
    countyId:    $address->county_id,
    subCountyId: $address->sub_county_id,
    weightKg:    $cartService->getWeight(),
    orderAmount: $cartService->getSubtotal(),
);
```

**What it does:**

1. Resolves the shipping zone for the address
2. Loads all active `flat` and `pus` methods
3. Skips `flat` if `zone->is_delivery_available = false`
4. Always runs `pus` regardless of zone (main store is always available as pickup)
5. Sorts: free first → by cost → by delivery speed

**Note:** Quote requests are handled through a separate quote basket flow, not the cart checkout.

---

## Checkout Flow

```
checkout.addresses.index / create
        ↓  (address selected → saved to CheckoutSession)
checkout.shipping
        ↓  (method confirmed → saved to CheckoutSession)
checkout.payment-methods  (if custom gateway)
        ↓
checkout.summary
        ↓
order placed → CheckoutSession::clear()
```

**CheckoutSession** is the single source of truth for all checkout state. Nothing touches `session('checkout.*')` directly — always go through the service.

Key methods:

| Method                 | Notes                                        |
| ---------------------- | -------------------------------------------- |
| `setAddressId(int)`    | Called when address is selected              |
| `getAddressId(): ?int` | Returns null if no address chosen yet        |
| `setShipping(array)`   | Stores full method + cost + breakdown        |
| `hasShipping(): bool`  | Guards navigation to summary                 |
| `clear()`              | Call after order is placed. Wipes all state. |

---

## Delivery Windows

Delivery windows (`estimated_days_min` / `estimated_days_max`) are stored per rate row at seed time. They are the same for all weight tiers within the same method and zone — weight affects cost only, not speed.

**Current windows:**

| Method   | Zone    | Min days | Max days |
| -------- | ------- | -------- | -------- |
| Standard | Nairobi | 1        | 2        |
| Pickup   | Nairobi | 2        | 3        |

The pickup window is `+1 day` vs standard to account for the station transfer step.

**To change delivery windows** without re-seeding:

```sql
UPDATE shipping_rates
SET estimated_days_min = 1, estimated_days_max = 2
WHERE shipping_method_id = (SELECT id FROM shipping_methods WHERE code = 'standard');

UPDATE shipping_rates
SET estimated_days_min = 2, estimated_days_max = 3
WHERE shipping_method_id = (SELECT id FROM shipping_methods WHERE code = 'pickup');
```

**To add windows for a new zone**, add an entry to `$deliveryWindows` in `ShippingSeeder::createShippingRates()`:

```php
$deliveryWindows = [
    'NAIROBI'   => ['standard' => ['min' => 1, 'max' => 2], 'pickup' => ['min' => 2, 'max' => 3]],
    'COAST'     => ['standard' => ['min' => 2, 'max' => 4], 'pickup' => ['min' => 3, 'max' => 5]],
    'UPCOUNTRY' => ['standard' => ['min' => 3, 'max' => 5], 'pickup' => ['min' => 4, 'max' => 6]],
];
```

The fallback for unmapped zone keys is `['standard' => [3, 5], 'pickup' => [4, 6]]`.

---

## Pickup Station Fallback

**The problem this solves:** Upcountry customers have `is_delivery_available = false` on their zone, so flat/doorstep delivery is blocked. Without a fallback, PUS also fails because no rates exist for the upcountry zone — leaving the customer with zero shipping options.

**The solution:** PUS always resolves rates from the primary station's zone (Nairobi), and `resolveStations()` always includes primary stations. Upcountry customers always see the pickup option.

**Configuration checklist:**

- Exactly one station must have `is_primary = true`
- That station must have `status = active`
- Its county must have a `shippingZone` with PUS rates seeded
- PUS rates must exist for that zone + method combination

**Adding a second primary station** in future: set `is_primary = true` on the new station. Both will appear at checkout for all customers. If you want regional primaries (e.g. one for Nairobi, one for Mombasa), you will need to extend `resolveStations()` to pick the nearest primary by zone rather than including all of them.

---

## Expanding to New Zones

To open delivery to a new region (e.g. Coast):

1. **Add a zone row:**

```php
ShippingZone::create([
    'name' => 'Coast',
    'code' => 'coast',
    'status' => ShippingZoneStatus::ACTIVE->value,
    'is_delivery_available' => true, // flip when ready
]);
```

2. **Assign counties to the zone** — update `shipping_zone_id` on the relevant county rows, or update `counties.json` and re-seed.

3. **Add shipping rates** for the new zone — run `createShippingRates()` logic targeting the new zone, or insert rows directly.

4. **Add delivery windows** to `$deliveryWindows` in the seeder (for documentation consistency).

5. **Add PUS rate addons** if the pickup method applies to this zone.

6. **Add a free shipping rule** if the promotion extends to this zone.

No schema changes are required — the architecture handles new zones entirely through data.

---

## Free Shipping Rules

Currently all delivery is free (`min_order_amount = 0`, `price = 0` on all rates). The rate rows and addon rows exist so the engines have valid data to resolve — when you introduce paid shipping, only the `price` and `addon_amount` columns need updating. Nothing structural changes.

**The nationwide placeholder rule** (`status = inactive`, `min_order_amount = 10000`, `max_weight = 20`) is a template for future promotions. Activate it by setting `status = active` and adjusting thresholds.

**To introduce a time-limited promotion:**

```php
FreeShippingRule::create([
    'name' => 'Black Friday Free Shipping',
    'shipping_zone_id' => $nairobiZone->id,
    'shipping_method_id' => null, // applies to all methods
    'min_order_amount' => 2000,
    'max_weight' => null,
    'starts_at' => '2025-11-29 00:00:00',
    'ends_at'   => '2025-11-30 23:59:59',
    'status' => FreeShippingRuleStatus::SCHEDULED->value,
]);
```

A scheduled job must transition `scheduled → active → expired` based on timestamps.

---

## Known Gotchas

**1. PUS rates must always be seeded for the primary station's zone**
`PusEngine` resolves rates against the primary station's county zone. If that zone has no PUS rate rows, upcountry customers will see no shipping options. Always run `createRateAddons()` after adding new PUS rates.

**2. `weight_label` must match actual min/max values**
The label is shown on invoices. A mismatch like `Medium (5–20 Kg)` with `min_weight = 5.1` is a display bug. Keep them consistent.

**3. Area `shipping_zone_id` should be null by default**
Only set an area's zone explicitly when it genuinely ships differently to its parent county. Incorrect overrides silently route customers to the wrong rate bracket.

**4. Never delete `expired` or `deprecated` rows**
Historical delivery orders reference these rows. Deleting them breaks invoice regeneration and reporting queries.

**5. `cost_breakdown` is the invoicing source of truth**
Never recalculate cost from FK references on a delivery order. Always read `cost_breakdown`. The FKs are for querying only.

**6. `resolveStations()` uses parameterised bindings**
The `orderByRaw` clause uses `?` bindings — do not interpolate `$countyId` directly into the string. SQL injection risk.

**7. Delivery windows are stored at seed time**
Changing `$deliveryWindows` in the seeder only affects new seeds. Update existing rows directly in the database if you need to change windows in production.

**8. `CheckoutSession::clear()` must be called after order placement**
Failing to clear the session leaves stale shipping/address/payment state that bleeds into the next checkout attempt.

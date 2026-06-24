<?php

use App\Enums\ProductLinkType;
use App\Models\Product;
use App\Models\ProductLink;
use App\Support\StorefrontSession;
use Livewire\Livewire;

/** Link $accessory to $parent as an accessory with the given semantics. */
function linkAccessory(Product $parent, Product $accessory, bool $required = false, int $qty = 1): void
{
    ProductLink::create([
        'product_id' => $parent->id,
        'linked_product_id' => $accessory->id,
        'type' => ProductLinkType::ACCESSORY,
        'is_required' => $required,
        'default_quantity' => $qty,
        'sort_order' => 0,
    ]);
}

it('badges the required quantity on the PDP accessory carousel', function () {
    $oven = Product::factory()->published()->create();
    $trays = Product::factory()->published()->create(['name' => 'Baking Tray GN 1/1']);
    linkAccessory($oven, $trays, required: true, qty: 12);

    Livewire::test('pages::storefront.product', ['product' => $oven])
        ->assertSee('Baking Tray GN 1/1')
        ->assertSee('Required ×12');
});

it('does not badge optional accessories on the carousel', function () {
    $oven = Product::factory()->published()->create();
    $cleaner = Product::factory()->published()->create(['name' => 'Oven Cleaning Kit']);
    linkAccessory($oven, $cleaner, required: false, qty: 1);

    Livewire::test('pages::storefront.product', ['product' => $oven])
        ->assertSee('Oven Cleaning Kit')
        ->assertDontSee('Needs');
});

it('opens the accessory prompt after adding a product that has accessories', function () {
    $oven = Product::factory()->published()->create(['name' => 'Convection Oven']);
    $trays = Product::factory()->published()->create(['name' => 'Baking Tray']);
    linkAccessory($oven, $trays, required: true, qty: 12);

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $oven->slug)
        ->assertSet('showAccessoryModal', true)
        ->assertSet('accessorySelections.'.$trays->slug.'.checked', true)
        ->assertSet('accessorySelections.'.$trays->slug.'.qty', 12);

    // The oven itself is already in the cart; the prompt is only for extras.
    expect(StorefrontSession::cartQuantity($oven->slug))->toBe(1);
});

it('seeds different default quantities per parent for the same accessory', function () {
    $ovenA = Product::factory()->published()->create();
    $ovenB = Product::factory()->published()->create();
    $trays = Product::factory()->published()->create();
    linkAccessory($ovenA, $trays, required: true, qty: 12);
    linkAccessory($ovenB, $trays, required: true, qty: 6);

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $ovenA->slug)
        ->assertSet('accessorySelections.'.$trays->slug.'.qty', 12);

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $ovenB->slug)
        ->assertSet('accessorySelections.'.$trays->slug.'.qty', 6);
});

it('does not open the prompt for a product with no accessories', function () {
    $simple = Product::factory()->published()->create();

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $simple->slug)
        ->assertSet('showAccessoryModal', false);

    expect(StorefrontSession::cartQuantity($simple->slug))->toBe(1);
});

it('does not prompt when adding a specific variant', function () {
    $oven = Product::factory()->published()->create();
    $trays = Product::factory()->published()->create();
    linkAccessory($oven, $trays, required: true, qty: 4);

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $oven->slug, 1, 999)
        ->assertSet('showAccessoryModal', false);
});

it('ignores accessories that are not published', function () {
    $oven = Product::factory()->published()->create();
    $draftTrays = Product::factory()->create(); // factory default status = draft
    linkAccessory($oven, $draftTrays, required: true, qty: 4);

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $oven->slug)
        ->assertSet('showAccessoryModal', false);
});

it('adds the selected accessories as separate line items', function () {
    $oven = Product::factory()->published()->create();
    $trays = Product::factory()->published()->create();
    linkAccessory($oven, $trays, required: true, qty: 6);

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $oven->slug)
        ->set('accessorySelections.'.$trays->slug.'.qty', 8)
        ->call('addSelectedAccessories')
        ->assertSet('showAccessoryModal', false);

    expect(StorefrontSession::cartQuantity($oven->slug))->toBe(1)
        ->and(StorefrontSession::cartQuantity($trays->slug))->toBe(8);
});

it('adjusts accessory quantity via the counter and clamps at 1', function () {
    $oven = Product::factory()->published()->create();
    $trays = Product::factory()->published()->create();
    linkAccessory($oven, $trays, required: true, qty: 2);

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $oven->slug)
        ->call('incAccessoryQty', $trays->slug)
        ->assertSet('accessorySelections.'.$trays->slug.'.qty', 3)
        ->call('decAccessoryQty', $trays->slug)
        ->call('decAccessoryQty', $trays->slug)
        ->call('decAccessoryQty', $trays->slug) // would go to 0, clamps at 1
        ->assertSet('accessorySelections.'.$trays->slug.'.qty', 1)
        ->call('addSelectedAccessories');

    expect(StorefrontSession::cartQuantity($trays->slug))->toBe(1);
});

it('skips unchecked accessories when confirming', function () {
    $oven = Product::factory()->published()->create();
    $trays = Product::factory()->published()->create();
    linkAccessory($oven, $trays, qty: 4);

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $oven->slug)
        ->set('accessorySelections.'.$trays->slug.'.checked', false)
        ->call('addSelectedAccessories');

    expect(StorefrontSession::cartQuantity($trays->slug))->toBe(0);
});

it('ignores accessory slugs that were not offered by the prompt', function () {
    $oven = Product::factory()->published()->create();
    $trays = Product::factory()->published()->create();
    $rogue = Product::factory()->published()->create();
    linkAccessory($oven, $trays, qty: 1);

    Livewire::test('pages::storefront.catalog')
        ->call('addToCart', $oven->slug)
        ->set('accessorySelections.'.$rogue->slug, ['checked' => true, 'qty' => 5])
        ->call('addSelectedAccessories');

    expect(StorefrontSession::cartQuantity($rogue->slug))->toBe(0);
});

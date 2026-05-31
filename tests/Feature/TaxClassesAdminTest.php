<?php

use App\Models\Product;
use App\Models\TaxClass;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('loads the tax classes admin index', function () {
    $this->get(route('admin.tax-classes.index'))->assertOk();
});

it('creates a tax class', function () {
    Livewire::test('pages::admin.tax-classes.index')
        ->call('openCreate')
        ->set('name', 'Reduced rate')
        ->set('rate', 8)
        ->call('save')
        ->assertHasNoErrors();

    expect(TaxClass::where('slug', 'reduced-rate')->first()?->rate)->toBe('8.00');
});

it('rejects a rate above 100 percent', function () {
    Livewire::test('pages::admin.tax-classes.index')
        ->call('openCreate')
        ->set('name', 'Bad')
        ->set('rate', 150)
        ->call('save')
        ->assertHasErrors(['rate']);
});

it('refuses to delete a tax class that is assigned to products', function () {
    $class = TaxClass::create(['name' => 'Standard', 'slug' => 'standard', 'rate' => 16, 'is_active' => true]);
    Product::factory()->create(['tax_class_id' => $class->id]);

    Livewire::test('pages::admin.tax-classes.index')->call('delete', $class->id);

    expect(TaxClass::find($class->id))->not->toBeNull();
});

it('deletes an unused tax class', function () {
    $class = TaxClass::create(['name' => 'Spare', 'slug' => 'spare', 'rate' => 5, 'is_active' => true]);

    Livewire::test('pages::admin.tax-classes.index')->call('delete', $class->id);

    expect(TaxClass::find($class->id))->toBeNull();
});

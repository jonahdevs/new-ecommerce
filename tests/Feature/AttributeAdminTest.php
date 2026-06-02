<?php

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

// ─── Index ────────────────────────────────────────────────────────────────────

it('loads the attributes index', function () {
    $this->get(route('admin.attributes.index'))->assertOk();
});

it('lists attributes on the index', function () {
    Attribute::factory()->create(['name' => 'Material']);
    Attribute::factory()->create(['name' => 'Colour']);

    $this->get(route('admin.attributes.index'))
        ->assertSee('Material')
        ->assertSee('Colour');
});

it('deletes an attribute from the index', function () {
    $attribute = Attribute::factory()->create(['name' => 'Size']);

    Livewire::test('pages::admin.attributes.index')
        ->call('delete', $attribute->id)
        ->assertHasNoErrors();

    expect(Attribute::find($attribute->id))->toBeNull();
});

// ─── Create ───────────────────────────────────────────────────────────────────

it('loads the create attribute page', function () {
    $this->get(route('admin.attributes.create'))->assertOk();
});

it('creates an attribute and redirects to edit page', function () {
    Livewire::test('pages::admin.attributes.create')
        ->set('name', 'Material')
        ->set('slug', 'material')
        ->set('type', 'select')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $attribute = Attribute::firstWhere('slug', 'material');
    expect($attribute)->not->toBeNull()
        ->and($attribute->name)->toBe('Material')
        ->and($attribute->type)->toBe(AttributeType::SELECT);
});

it('auto-generates slug from name on create', function () {
    Livewire::test('pages::admin.attributes.create')
        ->set('name', 'Watch Size')
        ->assertSet('slug', 'watch-size');
});

it('validates required fields on create', function () {
    Livewire::test('pages::admin.attributes.create')
        ->call('save')
        ->assertHasErrors(['name', 'slug']);
});

it('enforces unique slug on create', function () {
    Attribute::factory()->create(['slug' => 'material']);

    Livewire::test('pages::admin.attributes.create')
        ->set('name', 'Material')
        ->set('slug', 'material')
        ->call('save')
        ->assertHasErrors(['slug']);
});

// ─── Edit ─────────────────────────────────────────────────────────────────────

it('loads the edit attribute page', function () {
    $attribute = Attribute::factory()->create(['name' => 'Colour']);

    $this->get(route('admin.attributes.edit', $attribute))->assertOk();
});

it('saves attribute details on the edit page', function () {
    $attribute = Attribute::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    Livewire::test('pages::admin.attributes.edit', ['attribute' => $attribute])
        ->set('name', 'New Name')
        ->set('slug', 'new-name')
        ->call('save')
        ->assertHasNoErrors();

    expect($attribute->fresh()->name)->toBe('New Name');
});

it('enforces unique slug on edit ignoring self', function () {
    $attribute = Attribute::factory()->create(['slug' => 'colour']);

    Livewire::test('pages::admin.attributes.edit', ['attribute' => $attribute])
        ->set('name', 'Colour')
        ->set('slug', 'colour')
        ->call('save')
        ->assertHasNoErrors();
});

// ─── Values ───────────────────────────────────────────────────────────────────

it('shows existing values on the edit page', function () {
    $attribute = Attribute::factory()->create(['name' => 'Material']);
    AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'label' => 'Steel', 'value' => 'steel']);

    $this->get(route('admin.attributes.edit', $attribute))->assertSee('Steel');
});

it('adds a value on the edit page', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    Livewire::test('pages::admin.attributes.edit', ['attribute' => $attribute])
        ->set('valueLabel', 'Stainless Steel')
        ->set('valueValue', 'stainless-steel')
        ->call('addValue')
        ->assertHasNoErrors();

    expect(AttributeValue::where('attribute_id', $attribute->id)->where('value', 'stainless-steel')->exists())->toBeTrue();
});

it('validates required fields when adding a value', function () {
    $attribute = Attribute::factory()->create();

    Livewire::test('pages::admin.attributes.edit', ['attribute' => $attribute])
        ->call('addValue')
        ->assertHasErrors(['valueLabel', 'valueValue']);
});

it('toggles a value active status', function () {
    $attribute = Attribute::factory()->create();
    $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'is_active' => true]);

    Livewire::test('pages::admin.attributes.edit', ['attribute' => $attribute])
        ->call('toggleValueActive', $value->id);

    expect($value->fresh()->is_active)->toBeFalse();
});

it('opens the edit value modal with the correct data', function () {
    $attribute = Attribute::factory()->create();
    $value = AttributeValue::factory()->create([
        'attribute_id' => $attribute->id,
        'label' => 'Stainless Steel',
        'value' => 'stainless-steel',
        'sort_order' => 3,
    ]);

    Livewire::test('pages::admin.attributes.edit', ['attribute' => $attribute])
        ->call('openEditValue', $value->id)
        ->assertSet('showEditValueModal', true)
        ->assertSet('editValueLabel', 'Stainless Steel')
        ->assertSet('editValueValue', 'stainless-steel')
        ->assertSet('editValueSortOrder', 3);
});

it('saves an edited value', function () {
    $attribute = Attribute::factory()->create();
    $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'label' => 'Old Label', 'value' => 'old-value']);

    Livewire::test('pages::admin.attributes.edit', ['attribute' => $attribute])
        ->call('openEditValue', $value->id)
        ->set('editValueLabel', 'New Label')
        ->set('editValueValue', 'new-value')
        ->call('saveValue')
        ->assertHasNoErrors()
        ->assertSet('showEditValueModal', false);

    expect($value->fresh()->label)->toBe('New Label')
        ->and($value->fresh()->value)->toBe('new-value');
});

it('validates required fields when editing a value', function () {
    $attribute = Attribute::factory()->create();
    $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

    Livewire::test('pages::admin.attributes.edit', ['attribute' => $attribute])
        ->call('openEditValue', $value->id)
        ->set('editValueLabel', '')
        ->set('editValueValue', '')
        ->call('saveValue')
        ->assertHasErrors(['editValueLabel', 'editValueValue']);
});

it('deletes a value on the edit page', function () {
    $attribute = Attribute::factory()->create();
    $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

    Livewire::test('pages::admin.attributes.edit', ['attribute' => $attribute])
        ->call('deleteValue', $value->id)
        ->assertHasNoErrors();

    expect(AttributeValue::find($value->id))->toBeNull();
});

<?php

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    if (! Permission::where('name', 'edit.brands')->exists()) {
        Permission::create(['name' => 'edit.brands', 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->admin->givePermissionTo('edit.brands');

    $this->actingAs($this->admin);
});

test('brand changelog page displays activities in reverse chronological order', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->delete();

    sleep(1);
    $brand->update(['name' => 'Brand Update 1']);
    sleep(1);
    $brand->update(['name' => 'Brand Update 2']);
    sleep(1);
    $brand->update(['name' => 'Brand Update 3']);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(3)
        ->and($activities->first()->properties['attributes']['name'])->toBe('Brand Update 3')
        ->and($activities->last()->properties['attributes']['name'])->toBe('Brand Update 1');
});

test('brand changelog page paginates results with 20 entries per page', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->delete();

    for ($i = 1; $i <= 25; $i++) {
        $brand->update(['name' => "Brand Update {$i}"]);
    }

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(20)
        ->and($activities->hasMorePages())->toBeTrue();
});

test('brand changelog page shows timestamp, causer name, and field changes', function () {
    $brand = Brand::factory()->create(['name' => 'Original Name', 'is_active' => true]);

    Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->delete();

    $brand->update(['name' => 'Updated Name', 'is_active' => false]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id]);

    $component->assertSee('Updated Name')
        ->assertSee($this->admin->name)
        ->assertSee($this->admin->email)
        ->assertSee('Name:')
        ->assertSee('Active Status:')
        ->assertSee('Original Name');
});

test('brand changelog page displays dash for null values', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand', 'description' => 'Some description']);

    Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->delete();

    $brand->update(['name' => 'Updated Brand']);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id]);

    $component->assertSee('Name:');
});

test('brand changelog page displays System when causer is null', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->delete();

    auth()->logout();

    $brand->update(['name' => 'System Updated Brand']);

    $activity = Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull();

    $this->actingAs($this->admin);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id]);

    $component->assertSee('System');
});

test('brand changelog page shows empty state when no changes exist', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->delete();

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id]);

    $component->assertSee('No changes recorded')
        ->assertSee('Changes to this brand will appear here');
});

test('brand changelog page enforces authorization', function () {
    $brand = Brand::factory()->create(['name' => 'Test Brand']);

    $unauthorizedUser = User::factory()->create([
        'email' => 'unauthorized@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($unauthorizedUser);

    Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id])
        ->assertForbidden();
});

test('brand changelog page returns 404 for non-existent brand', function () {
    $this->expectException(ModelNotFoundException::class);

    Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => 99999]);
});

test('brand changelog page formats field labels correctly', function () {
    $brand = Brand::factory()->create([
        'name' => 'Test Brand',
        'is_active' => true,
    ]);

    Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->delete();

    $brand->update([
        'name' => 'Updated Brand',
        'is_active' => false,
    ]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id]);

    $component->assertSee('Name:')
        ->assertSee('Active Status:');
});

test('brand changelog page formats is_active values correctly', function () {
    $brand = Brand::factory()->create(['is_active' => true]);

    Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->delete();

    $brand->update(['is_active' => false]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id]);

    $component->assertSee('Active Status:')
        ->assertSee('Active')
        ->assertSee('Inactive');
});

test('brand changelog page displays multiple field changes in single activity', function () {
    $brand = Brand::factory()->create([
        'name' => 'Original Name',
        'is_active' => true,
    ]);

    Activity::where('subject_type', Brand::class)
        ->where('subject_id', $brand->id)
        ->delete();

    $brand->update([
        'name' => 'Updated Name',
        'is_active' => false,
    ]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'brand', 'id' => $brand->id]);

    $component->assertSee('Name:')
        ->assertSee('Active Status:')
        ->assertSee('Original Name')
        ->assertSee('Updated Name')
        ->assertSee('Active')
        ->assertSee('Inactive');
});

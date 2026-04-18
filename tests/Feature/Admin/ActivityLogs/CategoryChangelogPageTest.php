<?php

use App\Models\Category;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Livewire\Livewire;

/**
 * Integration tests for Category changelog page
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**
 * 
 * Tests that the Category changelog page correctly:
 * - Displays activities in reverse chronological order
 * - Paginates results with 20 entries per page
 * - Shows timestamp, causer name, and field changes
 * - Displays "—" for null/missing values
 * - Enforces authorization
 */

beforeEach(function () {
    // Create the permission if it doesn't exist
    if (!\Spatie\Permission\Models\Permission::where('name', 'edit.categories')->exists()) {
        \Spatie\Permission\Models\Permission::create(['name' => 'edit.categories', 'guard_name' => 'web']);
    }

    // Create a staff user with category edit permission
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    // Give the admin permission to edit categories
    $this->admin->givePermissionTo('edit.categories');

    $this->actingAs($this->admin);
});

test('category changelog page displays activities in reverse chronological order', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    // Create multiple changes
    sleep(1);
    $category->update(['sort_order' => 10]);
    sleep(1);
    $category->update(['sort_order' => 20]);
    sleep(1);
    $category->update(['sort_order' => 30]);

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(3)
        ->and($activities->first()->properties['attributes']['sort_order'])->toBe(30)
        ->and($activities->last()->properties['attributes']['sort_order'])->toBe(10);
});

test('category changelog page paginates results with 20 entries per page', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    // Create 25 changes
    for ($i = 1; $i <= 25; $i++) {
        $category->update(['sort_order' => $i]);
    }

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(20)
        ->and($activities->hasMorePages())->toBeTrue();
});

test('category changelog page shows timestamp, causer name, and field changes', function () {
    $category = Category::factory()->create(['name' => 'Original Name', 'sort_order' => 10]);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $category->update(['name' => 'Updated Name', 'sort_order' => 20]);

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $component->assertSee('Updated Name')
        ->assertSee($this->admin->name)
        ->assertSee($this->admin->email)
        ->assertSee('Name:')
        ->assertSee('Sort Order:')
        ->assertSee('Original Name')
        ->assertSee('10');
});

test('category changelog page displays dash for null values', function () {
    $parentCategory = Category::factory()->create(['name' => 'Parent']);
    $category = Category::factory()->create(['parent_id' => $parentCategory->id]);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $category->update(['parent_id' => null]);

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $component->assertSee('Parent Category:')
        ->assertSee('—');
});

test('category changelog page displays System when causer is null', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    // Log out to simulate system change
    auth()->logout();

    $category->update(['sort_order' => 10]);

    // Log back in to view the changelog
    $this->actingAs($this->admin);

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $component->assertSee('System');
});

test('category changelog page shows empty state when no changes exist', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $component->assertSee('No changes recorded')
        ->assertSee('Changes to this category will appear here');
});

test('category changelog page enforces authorization', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    // Create a user without edit permission
    $unauthorizedUser = User::factory()->create([
        'email' => 'unauthorized@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($unauthorizedUser);

    Livewire::test('admin.changelog.category-changelog', ['id' => $category->id])
        ->assertForbidden();
});

test('category changelog page returns 404 for non-existent category', function () {
    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test('admin.changelog.category-changelog', ['id' => 99999]);
});

test('category changelog page formats field labels correctly', function () {
    $parentCategory = Category::factory()->create(['name' => 'Parent']);
    $category = Category::factory()->create([
        'name' => 'Test Category',
        'parent_id' => $parentCategory->id,
        'status' => 'draft',
        'sort_order' => 10,
    ]);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $newParent = Category::factory()->create(['name' => 'New Parent']);
    $category->update([
        'name' => 'Updated Category',
        'parent_id' => $newParent->id,
        'status' => 'active',
        'sort_order' => 20,
    ]);

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $component->assertSee('Name:')
        ->assertSee('Parent Category:')
        ->assertSee('Status:')
        ->assertSee('Sort Order:');
});

test('category changelog page formats status values correctly', function () {
    $category = Category::factory()->create(['status' => 'draft']);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $category->update(['status' => 'active']);

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $component->assertSee('Status:')
        ->assertSee('Draft')
        ->assertSee('Active');
});

test('category changelog page displays parent category names', function () {
    $parentCategory = Category::factory()->create(['name' => 'Parent Category']);
    $newParentCategory = Category::factory()->create(['name' => 'New Parent Category']);
    $category = Category::factory()->create(['parent_id' => $parentCategory->id]);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $category->update(['parent_id' => $newParentCategory->id]);

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $component->assertSee('Parent Category:')
        ->assertSee('Parent Category')
        ->assertSee('New Parent Category');
});

test('category changelog page displays multiple field changes in single activity', function () {
    $parentCategory = Category::factory()->create(['name' => 'Parent']);
    $category = Category::factory()->create([
        'name' => 'Original Name',
        'parent_id' => $parentCategory->id,
        'status' => 'draft',
        'sort_order' => 10,
    ]);

    // Clear any initial activity logs from category creation
    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $newParent = Category::factory()->create(['name' => 'New Parent']);
    $category->update([
        'name' => 'Updated Name',
        'parent_id' => $newParent->id,
        'status' => 'active',
        'sort_order' => 20,
    ]);

    $component = Livewire::test('admin.changelog.category-changelog', ['id' => $category->id]);

    $component->assertSee('Name:')
        ->assertSee('Parent Category:')
        ->assertSee('Status:')
        ->assertSee('Sort Order:')
        ->assertSee('Original Name')
        ->assertSee('Updated Name')
        ->assertSee('Parent')
        ->assertSee('New Parent')
        ->assertSee('Draft')
        ->assertSee('Active')
        ->assertSee('10')
        ->assertSee('20');
});

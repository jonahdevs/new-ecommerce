<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    if (! Permission::where('name', 'edit.categories')->exists()) {
        Permission::create(['name' => 'edit.categories', 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->admin->givePermissionTo('edit.categories');

    $this->actingAs($this->admin);
});

test('category changelog page displays activities in reverse chronological order', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    sleep(1);
    $category->update(['sort_order' => 10]);
    sleep(1);
    $category->update(['sort_order' => 20]);
    sleep(1);
    $category->update(['sort_order' => 30]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(3)
        ->and($activities->first()->properties['attributes']['sort_order'])->toBe(30)
        ->and($activities->last()->properties['attributes']['sort_order'])->toBe(10);
});

test('category changelog page paginates results with 20 entries per page', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    for ($i = 1; $i <= 25; $i++) {
        $category->update(['sort_order' => $i]);
    }

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(20)
        ->and($activities->hasMorePages())->toBeTrue();
});

test('category changelog page shows timestamp, causer name, and field changes', function () {
    $category = Category::factory()->create(['name' => 'Original Name', 'sort_order' => 10]);

    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $category->update(['name' => 'Updated Name', 'sort_order' => 20]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

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

    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $category->update(['parent_id' => null]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

    $component->assertSee('Parent Category:')
        ->assertSee('—');
});

test('category changelog page displays System when causer is null', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    auth()->logout();

    $category->update(['sort_order' => 10]);

    $this->actingAs($this->admin);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

    $component->assertSee('System');
});

test('category changelog page shows empty state when no changes exist', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

    $component->assertSee('No changes recorded')
        ->assertSee('Changes to this category will appear here');
});

test('category changelog page enforces authorization', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);

    $unauthorizedUser = User::factory()->create([
        'email' => 'unauthorized@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($unauthorizedUser);

    Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id])
        ->assertForbidden();
});

test('category changelog page returns 404 for non-existent category', function () {
    $this->expectException(ModelNotFoundException::class);

    Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => 99999]);
});

test('category changelog page formats field labels correctly', function () {
    $parentCategory = Category::factory()->create(['name' => 'Parent']);
    $category = Category::factory()->create([
        'name' => 'Test Category',
        'parent_id' => $parentCategory->id,
        'status' => 'draft',
        'sort_order' => 10,
    ]);

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

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

    $component->assertSee('Name:')
        ->assertSee('Parent Category:')
        ->assertSee('Status:')
        ->assertSee('Sort Order:');
});

test('category changelog page formats status values correctly', function () {
    $category = Category::factory()->create(['status' => 'draft']);

    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $category->update(['status' => 'active']);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

    $component->assertSee('Status:')
        ->assertSee('Draft')
        ->assertSee('Active');
});

test('category changelog page displays parent category names', function () {
    $parentCategory = Category::factory()->create(['name' => 'Parent Category']);
    $newParentCategory = Category::factory()->create(['name' => 'New Parent Category']);
    $category = Category::factory()->create(['parent_id' => $parentCategory->id]);

    Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)
        ->delete();

    $category->update(['parent_id' => $newParentCategory->id]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

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

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'category', 'id' => $category->id]);

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

<?php

use App\Models\Category;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    actingAsAdmin();
    Storage::fake('public');
});

it('attaches uploaded images to the correct media collections on create', function () {
    Livewire::test('pages::admin.categories.create')
        ->set('name', 'Cooking Ranges')
        ->set('pendingImage', UploadedFile::fake()->image('banner.jpg', 1600, 500))
        ->set('pendingThumbnail', UploadedFile::fake()->image('square.jpg', 800, 800))
        ->set('pendingIcon', UploadedFile::fake()->image('icon.png', 128, 128))
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::firstWhere('slug', 'cooking-ranges');

    expect($category->getFirstMedia('banner'))->not->toBeNull()
        ->and($category->getFirstMedia('square'))->not->toBeNull()
        ->and($category->getFirstMedia('icon'))->not->toBeNull()
        ->and($category->banner_url)->not->toBeNull()
        ->and($category->square_url)->not->toBeNull();
});

it('falls back to the banner for the square url until a square image exists', function () {
    $category = Category::create(['name' => 'Refrigeration', 'slug' => 'refrigeration', 'status' => 'active']);

    $banner = UploadedFile::fake()->image('banner.jpg', 1600, 500);
    $category->addMedia($banner->getRealPath())
        ->usingFileName('banner.jpg')
        ->toMediaCollection('banner');

    expect($category->getFirstMedia('square'))->toBeNull()
        ->and($category->square_url)->not->toBeNull()
        ->and($category->square_url)->toBe($category->banner_url);
});

it('replaces the banner when a new one is uploaded on edit (single file collection)', function () {
    $category = Category::create(['name' => 'Sinks', 'slug' => 'sinks', 'status' => 'active']);
    $old = UploadedFile::fake()->image('old.jpg', 1600, 500);
    $category->addMedia($old->getRealPath())
        ->usingFileName('old.jpg')
        ->toMediaCollection('banner');

    Livewire::test('pages::admin.categories.edit', ['category' => $category])
        ->set('pendingImage', UploadedFile::fake()->image('new.jpg', 1600, 500))
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();

    expect($category->getMedia('banner'))->toHaveCount(1)
        ->and($category->getFirstMedia('banner')->file_name)->toBe('new.jpg');
});

it('exposes a square thumb url once a main image exists, and falls back otherwise', function () {
    $category = Category::create(['name' => 'Mixers', 'slug' => 'mixers', 'status' => 'active']);

    // No media yet -> falls back (to square_url, which is null here).
    expect($category->image_thumb_url)->toBeNull();

    $square = UploadedFile::fake()->image('square.jpg', 800, 800);
    $category->addMedia($square->getRealPath())
        ->usingFileName('square.jpg')
        ->toMediaCollection('square');

    $category->refresh();

    expect($category->getFirstMediaUrl('square', 'thumb'))->not->toBe('')
        ->and($category->image_thumb_url)->toBe($category->getFirstMediaUrl('square', 'thumb'));
});

it('generates an inline base64 LQIP placeholder for the banner', function () {
    $category = Category::create(['name' => 'Grills', 'slug' => 'grills', 'status' => 'active']);

    expect($category->banner_placeholder)->toBeNull();

    $banner = UploadedFile::fake()->image('banner.jpg', 1600, 500);
    $category->addMedia($banner->getRealPath())
        ->usingFileName('banner.jpg')
        ->toMediaCollection('banner');

    $category->refresh();

    expect($category->banner_placeholder)
        ->toStartWith('data:')
        ->toContain('base64,');
});

it('renders the admin category index with an image column', function () {
    Category::create(['name' => 'Mixers', 'slug' => 'mixers', 'status' => 'active']);

    Livewire::test('pages::admin.categories.index')
        ->assertOk()
        ->assertSee('Slug')
        ->assertSee('Mixers');
});

it('backfills category media from legacy columns via the sync command', function () {
    $path = UploadedFile::fake()->image('legacy.jpg', 1600, 500)->store('categories', 'public');

    $category = Category::create([
        'name' => 'Fryers',
        'slug' => 'fryers',
        'status' => 'active',
        'image' => $path,
    ]);

    expect($category->getFirstMedia('banner'))->toBeNull();

    $this->artisan('categories:sync-media')->assertSuccessful();

    expect($category->refresh()->getFirstMedia('banner'))->not->toBeNull();
});

it('removes a category banner via the edit form', function () {
    $category = Category::create(['name' => 'Ovens', 'slug' => 'ovens', 'status' => 'active']);
    $banner = UploadedFile::fake()->image('banner.jpg', 1600, 500);
    $category->addMedia($banner->getRealPath())
        ->usingFileName('banner.jpg')
        ->toMediaCollection('banner');

    Livewire::test('pages::admin.categories.edit', ['category' => $category])
        ->call('removeImage');

    expect($category->refresh()->getFirstMedia('banner'))->toBeNull();
});

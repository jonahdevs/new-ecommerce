<?php

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Log::spy();
});

test('storeWithWebP returns both original and WebP paths', function () {
    $service = new ImageService;
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $result = $service->storeWithWebP($file, 'products');

    expect($result)->toHaveKeys(['original', 'webp']);
    expect($result['original'])->toBeString();
    expect($result['webp'])->toBeString();
    expect($result['webp'])->toEndWith('.webp');
});

test('WebP file is created with correct extension', function () {
    $service = new ImageService;
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $result = $service->storeWithWebP($file, 'products');

    expect($result['webp'])->toEndWith('.webp');
    expect($result['original'])->not->toEndWith('.webp');
});

test('WebP file is stored in same directory as original', function () {
    $service = new ImageService;
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $result = $service->storeWithWebP($file, 'products');

    $originalDir = dirname($result['original']);
    $webpDir = dirname($result['webp']);

    expect($originalDir)->toBe($webpDir);
});

test('both original and WebP files exist in storage', function () {
    $service = new ImageService;
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $result = $service->storeWithWebP($file, 'products');

    Storage::disk('public')->assertExists($result['original']);
    Storage::disk('public')->assertExists($result['webp']);
});

test('generateWebP creates WebP from existing image', function () {
    $service = new ImageService;
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    // Store original first
    $originalPath = $file->store('products', 'public');

    // Use reflection to access protected method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateWebP');
    $method->setAccessible(true);

    $webpPath = $method->invoke($service, $originalPath, 'public');

    expect($webpPath)->toBeString();
    expect($webpPath)->toEndWith('.webp');
    Storage::disk('public')->assertExists($webpPath);
});

test('service handles invalid image gracefully', function () {
    $service = new ImageService;
    // Create a fake text file instead of image
    $file = UploadedFile::fake()->create('invalid.txt', 100, 'text/plain');

    $result = $service->storeWithWebP($file, 'products');

    // Should still store the original file but WebP should be null
    expect($result)->toHaveKeys(['original', 'webp']);
    expect($result['original'])->toBeString();
    expect($result['webp'])->toBeNull();

    // Should log a warning (WebP conversion failed gracefully, original still stored)
    Log::shouldHaveReceived('warning')->once();
});

test('service works with different storage disks', function () {
    Storage::fake('local');

    $service = new ImageService;
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $result = $service->storeWithWebP($file, 'products', 'local');

    expect($result)->toHaveKeys(['original', 'webp']);
    Storage::disk('local')->assertExists($result['original']);
    Storage::disk('local')->assertExists($result['webp']);
});

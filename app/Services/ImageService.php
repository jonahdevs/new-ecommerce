<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    /**
     * Store image and generate WebP variant
     *
     * @param UploadedFile $file
     * @param string $directory Storage directory path
     * @param string $disk Storage disk name (default: 'public')
     * @return array ['original' => string, 'webp' => string|null]
     */
    public function storeWithWebP(
        UploadedFile $file,
        string $directory,
        string $disk = 'public'
    ): array {
        try {
            // Store the original image
            $originalPath = $file->store($directory, $disk);

            // Generate WebP variant
            $webpPath = $this->generateWebP($originalPath, $disk);

            return [
                'original' => $originalPath,
                'webp' => $webpPath,
            ];
        } catch (\Exception $e) {
            Log::error('WebP conversion failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'directory' => $directory,
                'disk' => $disk,
                'user_id' => auth()->id(),
            ]);

            // Fallback: store original only
            return [
                'original' => $file->store($directory, $disk),
                'webp' => null,
            ];
        }
    }

    /**
     * Generate WebP variant from existing image
     *
     * @param string $originalPath
     * @param string $disk
     * @return string|null WebP path or null on failure
     */
    protected function generateWebP(
        string $originalPath,
        string $disk = 'public'
    ): ?string {
        try {
            // Get the full path to the original image
            $fullPath = Storage::disk($disk)->path($originalPath);

            // Create ImageManager with GD driver and read image
            $manager = ImageManager::gd();
            $image = $manager->read($fullPath);

            // Generate WebP path (replace extension with .webp)
            $webpPath = preg_replace('/\.[^.]+$/', '.webp', $originalPath);

            // Convert to WebP with 85% quality
            $encoded = $image->toWebp(quality: 85);

            // Store WebP content
            Storage::disk($disk)->put($webpPath, $encoded);

            return $webpPath;
        } catch (\Exception $e) {
            Log::warning('WebP generation failed', [
                'error' => $e->getMessage(),
                'path' => $originalPath,
                'disk' => $disk,
            ]);

            return null;
        }
    }
}
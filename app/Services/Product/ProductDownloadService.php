<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductDownload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductDownloadService
{
    /**
     * Save all downloads for a product
     */
    public function save(Product $product, array $downloads, ?int $variantId = null): void
    {
        foreach ($downloads as $index => $download) {
            if (!empty($download['id'])) {
                $this->updateDownload($download);
            } else {
                $this->createDownload($product, $download, $index, $variantId);
            }
        }
    }


    /**
     * Create a new download record
     */
    private function createDownload(
        Product $product,
        array $download,
        int $index,
        ?int $variantId = null
    ): void {
        if (empty($download['file'])) return;

        $file     = $download['file'];
        $filePath = $this->storeFile($file);

        ProductDownload::create([
            'product_id' => $product->id,
            'variant_id' => $variantId,  // ← add this
            'name'       => $download['name'] ?: $file->getClientOriginalName(),
            'file_path'  => $filePath,
            'file_name'  => $file->getClientOriginalName(),
            'file_type'  => $file->getClientOriginalExtension(),
            'file_size'  => $file->getSize(),
            'sort_order' => $index,
        ]);
    }

    /**
     * Update an existing download record
     */
    private function updateDownload(array $download): void
    {
        $record = ProductDownload::find($download['id']);
        if (!$record) return;

        $updateData = [
            'name'       => $download['name'] ?: $record->name,
            'sort_order' => $download['sort_order'] ?? $record->sort_order,
        ];

        if (!empty($download['file'])) {
            $file    = $download['file'];
            $newPath = $this->storeFile($file);

            if ($newPath) {
                $oldPath = $record->file_path;
                $updateData = array_merge($updateData, [
                    'file_path' => $newPath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                ]);
                $record->update($updateData);
                $this->deleteFile($oldPath);
                return;
            }
        }

        $record->update($updateData);
    }

    /**
     * Delete a specific download and its file
     */
    public function delete(ProductDownload $download): void
    {
        $this->deleteFile($download->file_path);
        $download->delete();
    }

    /**
     * Delete all downloads for a product
     */
    public function deleteAll(Product $product): void
    {
        $product->downloads()->get()->each(function ($download) {
            $this->deleteFile($download->file_path);
        });

        $product->downloads()->delete();
    }

    /**
     * Sync downloads — handle new, updated, and deleted
     */
    public function sync(
        Product $product,
        array $downloads,
        array $downloadsToDelete = [],
        ?int $variantId = null
    ): void {
        if (!empty($downloadsToDelete)) {
            $records = ProductDownload::whereIn('id', $downloadsToDelete)->get();
            foreach ($records as $record) {
                $this->delete($record);
            }
        }

        $this->save($product, $downloads, $variantId);
    }

    /**
     * Store file on private disk
     */
    private function storeFile($file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('downloads', $filename, 'private');

        if (!$path) {
            throw new \RuntimeException("Failed to store download file: {$filename}");
        }

        return $path;
    }

    /**
     * Delete file from private disk
     */
    private function deleteFile(?string $filePath): void
    {
        if ($filePath && Storage::disk('private')->exists($filePath)) {
            Storage::disk('private')->delete($filePath);
        }
    }
}

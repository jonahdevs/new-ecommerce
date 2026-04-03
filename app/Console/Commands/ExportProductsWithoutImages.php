<?php

namespace App\Console\Commands;

use App\Exports\ProductsWithoutImagesExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductsWithoutImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:export-without-images 
                            {--output= : Custom output filename (default: products_without_images.xlsx)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export products that have no image or empty image field to Excel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Scanning products.json for products without images...');
        $this->newLine();

        // Get products without images
        $productsWithoutImages = $this->getProductsWithoutImages();

        if ($productsWithoutImages->isEmpty()) {
            $this->info('✨ All products have images! No export needed.');

            return Command::SUCCESS;
        }

        $this->warn("📋 Found {$productsWithoutImages->count()} products without images:");
        $this->newLine();

        // Display summary table
        $this->displaySummary($productsWithoutImages);

        // Generate filename
        $filename = $this->option('output') ?: 'products_without_images_'.date('Y-m-d_His').'.xlsx';

        // Ensure .xlsx extension
        if (! str_ends_with($filename, '.xlsx')) {
            $filename .= '.xlsx';
        }

        // Create exports directory if it doesn't exist (in private storage)
        $exportsDir = storage_path('app/private/exports');
        if (! File::exists($exportsDir)) {
            File::makeDirectory($exportsDir, 0755, true);
        }

        $outputPath = $exportsDir.DIRECTORY_SEPARATOR.$filename;

        // Export to Excel
        $this->info('📊 Exporting to Excel...');

        try {
            Excel::store(
                new ProductsWithoutImagesExport($productsWithoutImages),
                'exports/'.$filename
            );

            $this->newLine();
            $this->info('✅ Export completed successfully!');
            $this->newLine();
            $this->line("📁 File saved to: {$outputPath}");

            // Check if file exists before getting size
            if (File::exists($outputPath)) {
                $this->line('📦 File size: '.$this->formatBytes(File::size($outputPath)));
            }

            $this->newLine();

            // Show how to access the file
            $this->comment('💡 You can find the file at:');
            $this->line("   {$outputPath}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Export failed: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Get products without images from products.json
     */
    private function getProductsWithoutImages()
    {
        $jsonPath = database_path('seeders/data/products.json');

        if (! File::exists($jsonPath)) {
            $this->error('❌ products.json not found at: '.$jsonPath);

            return collect();
        }

        $products = json_decode(File::get($jsonPath), true);

        if (! is_array($products)) {
            $this->error('❌ Invalid products.json format');

            return collect();
        }

        return collect($products)->filter(function ($product) {
            // Check if image field is missing, null, or empty string
            return ! isset($product['image'])
                || $product['image'] === null
                || $product['image'] === ''
                || trim($product['image']) === '';
        })->values();
    }

    /**
     * Display summary table of products without images
     */
    private function displaySummary($products)
    {
        $table = [];
        $count = 0;
        $maxDisplay = 10;

        foreach ($products as $product) {
            if ($count >= $maxDisplay) {
                break;
            }

            $table[] = [
                'name' => $product['name'] ?? 'N/A',
                'sku' => $product['sku'] ?? 'N/A',
                'category' => $product['category'] ?? 'N/A',
                'price' => isset($product['price']) ? 'KES '.number_format($product['price'], 2) : 'N/A',
            ];

            $count++;
        }

        $this->table(
            ['Product Name', 'SKU', 'Category', 'Price'],
            $table
        );

        if ($products->count() > $maxDisplay) {
            $remaining = $products->count() - $maxDisplay;
            $this->line("... and {$remaining} more products");
        }

        $this->newLine();
    }

    /**
     * Format bytes to human-readable size
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}

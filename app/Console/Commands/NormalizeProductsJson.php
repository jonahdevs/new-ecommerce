<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class NormalizeProductsJson extends Command
{
    protected $signature = 'products:normalize-json 
                            {--dry-run : Show what would be changed without modifying the file}
                            {--backup : Create a backup before modifying}';

    protected $description = 'Normalize products.json to ensure all products have consistent structure';

    // Required keys that every product must have
    protected array $requiredKeys = [
        'name' => null,
        'sku' => null,
        'brand' => null,
        'model_number' => null,
        'category' => null,
        'price' => null,
        'quantity' => null,
        'image' => null,
        'short_description' => null,
        'description' => null,
    ];

    // Optional keys that should be included if they exist
    protected array $optionalKeys = [
        'gallery' => [],
        'accessories' => [],
    ];

    public function handle()
    {
        $jsonPath = database_path('seeders/data/products.json');

        if (! File::exists($jsonPath)) {
            $this->error('❌ products.json not found at: '.$jsonPath);

            return Command::FAILURE;
        }

        $this->info('🔍 Analyzing products.json...');
        $this->newLine();

        $products = json_decode(File::get($jsonPath), true);

        if (! is_array($products)) {
            $this->error('❌ Invalid products.json format');

            return Command::FAILURE;
        }

        // Analyze current structure
        $this->analyzeStructure($products);

        // Normalize products
        $normalizedProducts = $this->normalizeProducts($products);

        // Show changes
        $this->showChanges($products, $normalizedProducts);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('🔍 Dry run completed. No changes were made.');

            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (! $this->confirm('Do you want to proceed with normalizing the products.json?', true)) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        // Create backup if requested
        if ($this->option('backup')) {
            $backupPath = database_path('seeders/data/products.json.backup.'.date('Y-m-d_His'));
            File::copy($jsonPath, $backupPath);
            $this->info("📦 Backup created: {$backupPath}");
        }

        // Save normalized JSON
        File::put(
            $jsonPath,
            json_encode($normalizedProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $this->newLine();
        $this->info('✅ Products.json has been normalized successfully!');
        $this->newLine();

        return Command::SUCCESS;
    }

    private function analyzeStructure(array $products): void
    {
        $allKeys = [];
        $keyFrequency = [];

        foreach ($products as $product) {
            foreach (array_keys($product) as $key) {
                $allKeys[$key] = true;
                $keyFrequency[$key] = ($keyFrequency[$key] ?? 0) + 1;
            }
        }

        $this->line('📊 Found '.count($products).' products');
        $this->line('📋 Unique keys found: '.count($allKeys));
        $this->newLine();

        $this->table(
            ['Key', 'Frequency', 'Missing in'],
            collect($keyFrequency)
                ->map(function ($count, $key) use ($products) {
                    $missing = count($products) - $count;

                    return [
                        'key' => $key,
                        'frequency' => $count.' / '.count($products),
                        'missing' => $missing > 0 ? $missing.' products' : 'None',
                    ];
                })
                ->sortBy('key')
                ->values()
                ->toArray()
        );

        $this->newLine();
    }

    private function normalizeProducts(array $products): array
    {
        return array_map(function ($product) {
            $normalized = [];

            // Add all required keys first
            foreach ($this->requiredKeys as $key => $defaultValue) {
                $normalized[$key] = $product[$key] ?? $defaultValue;
            }

            // Add optional keys only if they exist and are not empty
            foreach ($this->optionalKeys as $key => $defaultValue) {
                if (isset($product[$key]) && ! empty($product[$key])) {
                    $normalized[$key] = $product[$key];
                }
            }

            // Add any other keys that might exist (preserve custom fields)
            foreach ($product as $key => $value) {
                if (! isset($normalized[$key])) {
                    $normalized[$key] = $value;
                }
            }

            return $normalized;
        }, $products);
    }

    private function showChanges(array $original, array $normalized): void
    {
        $changesCount = 0;
        $missingKeysAdded = [];

        foreach ($original as $index => $product) {
            $originalKeys = array_keys($product);
            $normalizedKeys = array_keys($normalized[$index]);

            $addedKeys = array_diff($normalizedKeys, $originalKeys);

            if (! empty($addedKeys)) {
                $changesCount++;
                foreach ($addedKeys as $key) {
                    $missingKeysAdded[$key] = ($missingKeysAdded[$key] ?? 0) + 1;
                }
            }
        }

        if ($changesCount > 0) {
            $this->warn("⚠️  {$changesCount} products will be updated");
            $this->newLine();
            $this->line('Keys to be added:');
            foreach ($missingKeysAdded as $key => $count) {
                $this->line("  • {$key}: will be added to {$count} products");
            }
        } else {
            $this->info('✨ All products already have consistent structure!');
        }

        $this->newLine();
    }
}

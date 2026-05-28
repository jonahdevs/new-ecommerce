<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('brands:import-images {source : Absolute path to the folder containing brand images}')]
#[Description('Copy referenced brand images from a source folder into storage/app/public/brands and update the DB.')]
class ImportBrandImages extends Command
{
    public function handle(): int
    {
        $source = rtrim($this->argument('source'), '/\\');

        if (! is_dir($source)) {
            $this->error("Source directory not found: {$source}");

            return self::FAILURE;
        }

        // Ensure the destination exists.
        Storage::disk('public')->makeDirectory('brands');

        $brands = Brand::whereNotNull('logo')->get();
        $copied = 0;
        $missing = 0;
        $skipped = 0;

        foreach ($brands as $brand) {
            $filename = basename($brand->logo);           // e.g. 1693399391__brand_tecnodom.jpg
            $sourcePath = $source.DIRECTORY_SEPARATOR.$filename;
            $destRelPath = 'brands/'.$filename;             // relative inside public disk

            if (Storage::disk('public')->exists($destRelPath)) {
                $skipped++;

                continue;
            }

            if (! file_exists($sourcePath)) {
                $this->warn("  [missing] {$filename}");
                $missing++;

                continue;
            }

            Storage::disk('public')->put(
                $destRelPath,
                file_get_contents($sourcePath)
            );

            $brand->update(['logo' => $destRelPath]);

            $this->line("  [copied]  {$filename}");
            $copied++;
        }

        $this->newLine();
        $this->info("Done — copied: {$copied}, already existed: {$skipped}, not found: {$missing}.");

        if ($missing > 0) {
            $this->warn('Run again with a different source path if some files live in a sub-folder.');
        }

        return self::SUCCESS;
    }
}

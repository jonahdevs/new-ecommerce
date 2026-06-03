<?php

namespace App\Console\Commands;

use App\Enums\CategoryStatus;
use App\Enums\ProductVisibility;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Settings\SeoSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

#[Signature('sitemap:generate')]
#[Description('Generate the public/sitemap.xml file')]
class GenerateSitemap extends Command
{
    public function handle(SeoSettings $seo): int
    {
        if (! $seo->generate_sitemap) {
            $this->info('Sitemap generation is disabled in settings. Skipping.');

            return self::SUCCESS;
        }

        $sitemap = Sitemap::create()
            ->add(Url::create(route('home'))->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))
            ->add(Url::create(route('catalog'))->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))
            ->add(Url::create(route('contact'))->setPriority(0.5)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));

        Category::query()
            ->where('status', CategoryStatus::ACTIVE)
            ->select(['id', 'slug', 'updated_at'])
            ->get()
            ->each(fn ($category) => $sitemap->add(
                Url::create(route('category.show', $category))
                    ->setLastModificationDate($category->updated_at)
                    ->setPriority(0.8)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ));

        Product::query()
            ->published()
            ->where('visibility', '!=', ProductVisibility::HIDDEN)
            ->select(['id', 'slug', 'canonical_url', 'updated_at'])
            ->get()
            ->each(fn ($product) => $sitemap->add(
                Url::create($product->canonical_url ?: route('product.show', $product))
                    ->setLastModificationDate($product->updated_at)
                    ->setPriority(0.7)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ));

        Page::query()
            ->published()
            ->select(['slug', 'updated_at'])
            ->get()
            ->each(fn ($page) => $sitemap->add(
                Url::create(route('page.show', $page->slug))
                    ->setLastModificationDate($page->updated_at)
                    ->setPriority(0.5)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ));

        $path = public_path('sitemap.xml');
        $sitemap->writeToFile($path);

        $this->info("Sitemap written to {$path}");

        return self::SUCCESS;
    }
}

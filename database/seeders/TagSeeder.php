<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Tags\Tag;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Product feature tags
            ['name' => 'Featured', 'type' => 'feature'],
            ['name' => 'New Arrival', 'type' => 'feature'],
            ['name' => 'Best Seller', 'type' => 'feature'],
            ['name' => 'On Sale', 'type' => 'feature'],
            ['name' => 'Limited Edition', 'type' => 'feature'],
            ['name' => 'Trending', 'type' => 'feature'],

            // Material tags
            ['name' => 'Leather', 'type' => 'material'],
            ['name' => 'Cotton', 'type' => 'material'],
            ['name' => 'Stainless Steel', 'type' => 'material'],
            ['name' => 'Aluminum', 'type' => 'material'],
            ['name' => 'Plastic', 'type' => 'material'],

            // General tags
            ['name' => 'Eco-Friendly', 'type' => null],
            ['name' => 'Handmade', 'type' => null],
            ['name' => 'Imported', 'type' => null],
            ['name' => 'Local', 'type' => null],
            ['name' => 'Warranty', 'type' => null],
        ];

        foreach ($tags as $tag) {
            Tag::findOrCreate($tag['name'], $tag['type']);
        }

        $this->command->info('Seeded '.count($tags).' tags.');
    }
}

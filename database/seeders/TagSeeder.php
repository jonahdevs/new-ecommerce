<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            [
                'name' => 'New Arrival',
                'slug' => 'new-arrival',
                'description' => 'Recently added products',
                'color' => '#10B981',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Best Seller',
                'slug' => 'best-seller',
                'description' => 'Top selling products',
                'color' => '#F59E0B',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Featured',
                'slug' => 'featured',
                'description' => 'Featured products on homepage',
                'color' => '#3B82F6',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Sale',
                'slug' => 'sale',
                'description' => 'Products on sale',
                'color' => '#EF4444',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Limited Edition',
                'slug' => 'limited-edition',
                'description' => 'Limited stock available',
                'color' => '#8B5CF6',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Trending',
                'slug' => 'trending',
                'description' => 'Currently trending products',
                'color' => '#EC4899',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Eco Friendly',
                'slug' => 'eco-friendly',
                'description' => 'Environmentally friendly products',
                'color' => '#059669',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'High-end premium products',
                'color' => '#D97706',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Clearance',
                'slug' => 'clearance',
                'description' => 'Clearance sale items',
                'color' => '#DC2626',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Exclusive',
                'slug' => 'exclusive',
                'description' => 'Exclusive to our store',
                'color' => '#7C3AED',
                'is_active' => true,
                'sort_order' => 10,
            ],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}

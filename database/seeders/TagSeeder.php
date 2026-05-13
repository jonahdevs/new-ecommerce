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
            ['name' => 'New Arrival', 'type' => 'badge', 'order_column' => 1, 'color' => '#10b981'], // emerald-500
            ['name' => 'Best Seller', 'type' => 'badge', 'order_column' => 2, 'color' => '#f59e0b'], // amber-500
            ['name' => 'Featured', 'type' => 'badge', 'order_column' => 3, 'color' => '#3b82f6'], // blue-500
            ['name' => 'Sale', 'type' => 'badge', 'order_column' => 4, 'color' => '#ef4444'], // red-500
            ['name' => 'Limited Edition', 'type' => 'badge', 'order_column' => 5, 'color' => '#a855f7'], // purple-500
            ['name' => 'Trending', 'type' => 'badge', 'order_column' => 6, 'color' => '#0ea5e9'], // sky-500
            ['name' => 'Eco Friendly', 'type' => 'badge', 'order_column' => 7, 'color' => '#16a34a'], // green-600
            ['name' => 'Premium', 'type' => 'badge', 'order_column' => 8, 'color' => '#475569'], // slate-600
            ['name' => 'Clearance', 'type' => 'badge', 'order_column' => 9, 'color' => '#f97316'], // orange-500
            ['name' => 'Exclusive', 'type' => 'badge', 'order_column' => 10, 'color' => '#7c3aed'], // violet-600
        ];

        foreach ($tags as $tag) {
            $created = Tag::findOrCreate($tag['name'], $tag['type']);
            $created->order_column = $tag['order_column'];
            $created->color = $tag['color'];
            $created->save();
        }
    }
}

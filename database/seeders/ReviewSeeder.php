<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🔄 Creating reviews for products...');

        $products = Product::all();
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('⚠️  No users found. Creating sample users...');
            $users = User::factory()->count(20)->create();
        }

        foreach ($products as $product) {
            // Random number of reviews per product (0-30)
            $reviewCount = rand(0, 30);

            if ($reviewCount === 0) {
                continue;
            }

            // Generate reviews with weighted rating distribution
            // More likely to have 4-5 star reviews
            $ratingDistribution = $this->getWeightedRatingDistribution($reviewCount);

            foreach ($ratingDistribution as $rating => $count) {
                Review::factory()
                    ->count($count)
                    ->create([
                        'product_id' => $product->id,
                        'user_id' => $users->random()->id,
                        'rating' => $rating,
                    ]);
            }

            $this->command->info("✅ Created {$reviewCount} reviews for: {$product->name}");
        }

        $totalReviews = Review::count();
        $this->command->info("✨ Total reviews created: {$totalReviews}");
    }

    /**
     * Get weighted rating distribution
     * Returns array like [5 => 10, 4 => 5, 3 => 2, 2 => 1, 1 => 1]
     */
    protected function getWeightedRatingDistribution(int $totalReviews): array
    {
        // Weighted probabilities (more positive reviews)
        $weights = [
            5 => 45, // 45% chance for 5 stars
            4 => 30, // 30% chance for 4 stars
            3 => 15, // 15% chance for 3 stars
            2 => 7,  // 7% chance for 2 stars
            1 => 3,  // 3% chance for 1 star
        ];

        $distribution = [];

        for ($i = 0; $i < $totalReviews; $i++) {
            $rating = $this->weightedRandom($weights);
            $distribution[$rating] = ($distribution[$rating] ?? 0) + 1;
        }

        return $distribution;
    }

    /**
     * Select a random item based on weights
     */
    protected function weightedRandom(array $weights): int
    {
        $rand = mt_rand(1, array_sum($weights));

        foreach ($weights as $key => $weight) {
            $rand -= $weight;
            if ($rand <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }
}

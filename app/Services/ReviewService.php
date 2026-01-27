<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

/**
 * Class ReviewService.
 */
class ReviewService
{
    /**
     * Rating distribution
     */
    public function ratingDistribution(Product $product)
    {
        $distribution = Review::query()
            ->approved()
            ->forProduct($product->id)
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->pluck('count', 'rating')
            ->toArray();

        // Ensure all ratings 1-5 are present
        $result = [];
        for ($i = 5; $i >= 1; $i--) {
            $result[$i] = $distribution[$i] ?? 0;
        }

        return $result;
    }

    public function totalReview(Product $product)
    {
        return Review::query()->approved()
            ->forProduct($product->id)
            ->count();
    }

    public function averageRating(Product $product)
    {
        $average = Review::query()
            ->approved()
            ->forProduct($product->id)
            ->avg('rating');

        return $average ? round($average, 2) : 0;
    }

    public function reviews(Product $product, $limit)
    {
        return $product
            ->reviews()
            ->approved()
            ->with(['user', 'images'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpfulness;
use DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class ReviewService.
 */
class ReviewService
{
    public function baseQuery(Product $product)
    {
        return Review::where('product_id', $product->id)
            ->approved()
            ->select(['id', 'product_id', 'user_id', 'rating', 'title', 'review_text', 'is_verified_purchase', 'helpful_count', 'not_helpful_count', 'created_at'])
            ->with([
                'user:id,name',
                'images' => fn ($q) => $q->select(['id', 'review_id', 'image_path']),
            ])
            ->latest();
    }

    public function forProductPage(Product $product, int $limit = 5)
    {
        return $this->baseQuery($product)
            ->limit($limit)
            ->get();
    }

    public function forReviewsPage(Product $product, array $filters = [])
    {
        return $this->baseQuery($product)
            ->when(isset($filters['rating']), function ($query) use ($filters) {
                $query->where('rating', $filters['rating']);
            })
            ->when(isset($filters['sort_by']), function ($query) use ($filters) {
                switch ($filters['sort_by']) {
                    case 'helpful':
                        $query->orderBy('helpful_count', 'desc');
                        break;
                    case 'highest':
                        $query->orderBy('rating', 'desc');
                        break;
                    case 'lowest':
                        $query->orderBy('rating', 'asc');
                        break;
                    case 'recent':
                    default:
                        $query->latest();
                        break;
                }
            })
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Get all review statistics for a product in a single aggregation query.
     *
     * Returns:
     *   - total       (int)   — approved review count
     *   - average     (float) — rounded to 1 decimal place, 0.0 when no reviews
     *   - distribution (array) — keys 5→1, each with 'count' and 'percentage'
     *
     * Replaces the previous getStatistics() which internally called
     * totalReview() + averageRating() + getDistributionWithPercentages(),
     * firing 4 separate queries for the same data.
     */
    public function getStatistics(Product $product): array
    {
        $row = $product->reviews()
            ->approved()
            ->selectRaw('
                COUNT(*)                                         AS total,
                COALESCE(ROUND(AVG(rating), 1), 0)              AS average,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END)     AS star_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END)     AS star_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END)     AS star_3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END)     AS star_2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END)     AS star_1
            ')
            ->first();

        $total = (int) ($row->total ?? 0);
        $average = (float) ($row->average ?? 0.0);

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = (int) ($row->{"star_{$star}"} ?? 0);
            $distribution[$star] = [
                'count' => $count,
                'percentage' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return compact('total', 'average', 'distribution');
    }

    /**
     * Kept for backwards compatibility — delegates to getStatistics().
     * Use getStatistics() directly where you need more than one of these values.
     */
    public function totalReview(Product $product): int
    {
        return $this->getStatistics($product)['total'];
    }

    /**
     * Kept for backwards compatibility — delegates to getStatistics().
     * Use getStatistics() directly where you need more than one of these values.
     */
    public function averageRating(Product $product): float
    {
        return $this->getStatistics($product)['average'];
    }

    /**
     * Kept for backwards compatibility — delegates to getStatistics().
     * Use getStatistics() directly where you need more than one of these values.
     */
    public function ratingDistribution(Product $product): array
    {
        return array_column(
            $this->getStatistics($product)['distribution'],
            'count',
        );
    }

    /**
     * Kept for backwards compatibility — delegates to getStatistics().
     */
    public function getDistributionWithPercentages(Product $product): array
    {
        return $this->getStatistics($product)['distribution'];
    }

    /**
     * Mark a review as helpful or not helpful.
     */
    public function vote(Review $review, bool $isHelpful): void
    {
        if (! Auth::check()) {
            throw new AuthenticationException('Authentication required.');
        }

        if ($review->user_id === Auth::id()) {
            throw new DomainException('self_vote');
        }

        DB::transaction(function () use ($review, $isHelpful) {
            $existingVote = ReviewHelpfulness::where('review_id', $review->id)
                ->where('user_id', Auth::id())
                ->first();

            if ($existingVote) {
                if ($existingVote->is_helpful === $isHelpful) {
                    // Same vote clicked again — toggle off
                    $existingVote->delete();
                } else {
                    // Switching vote type
                    $existingVote->update(['is_helpful' => $isHelpful]);
                    $review->decrement($isHelpful ? 'not_helpful_count' : 'helpful_count');
                    $review->increment($isHelpful ? 'helpful_count' : 'not_helpful_count');
                }
            } else {
                ReviewHelpfulness::create([
                    'review_id' => $review->id,
                    'user_id' => Auth::id(),
                    'is_helpful' => $isHelpful,
                ]);

                $review->increment($isHelpful ? 'helpful_count' : 'not_helpful_count');
            }

            $review->refresh();
        });
    }
}

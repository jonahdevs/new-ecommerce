<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpfulness;
use DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ReviewService.
 */
class ReviewService
{
    /**
     * Get rating distribution for a product
     *
     * @param Product $product
     * @return array
     */
    public function ratingDistribution(Product $product): array
    {
        $distribution = $product->reviews()
            ->approved()
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

    /**
     * Get total number of reviews for a product
     *
     * @param Product $product
     * @return int
     */
    public function totalReview(Product $product): int
    {
        return $product->reviews()
            ->approved()
            ->count();
    }

    /**
     * Get average rating for a product
     *
     * @param Product $product
     * @return float
     */
    public function averageRating(Product $product): float
    {
        $average = $product->reviews()
            ->approved()
            ->avg('rating');

        return $average ? round($average, 1) : 0.0;
    }

    /**
     * Get paginated reviews with sorting and filtering
     *
     * @param Product $product
     * @param string $sortBy
     * @param int|null $filterRating
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getReviews(Product $product, string $sortBy = 'recent', ?int $filterRating = null, int $perPage = 10)
    {
        $query = $product->reviews()
            ->approved()
            ->with(['user', 'images']);

        // Apply rating filter if set
        if ($filterRating !== null) {
            $query->where('rating', $filterRating);
        }

        // Apply sorting
        switch ($sortBy) {
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

        return $query->paginate($perPage);
    }

    /**
     * Mark review as helpful
     *
     * @param Review $review
     * @param bool $helpful
     * @return void
     */
    public function vote(Review $review, bool $isHelpful): void
    {
        if (!Auth::check()) {
            throw new AuthenticationException('Authentication required.');
        }

        // Prevent self-voting
        if ($review->user_id === Auth::id()) {
            throw new DomainException('self_vote');
        }

        DB::transaction(function () use ($review, $isHelpful) {
            $existingVote = ReviewHelpfulness::where('review_id', $review->id)
                ->where('user_id', Auth::id())
                ->first();

            if ($existingVote) {
                // If clicking the same vote again, remove it (toggle off)
                if ($existingVote->is_helpful === $isHelpful) {
                    $existingVote->delete();
                } else {
                    // If switching vote type, update it
                    $existingVote->update(['is_helpful' => $isHelpful]);

                    $review->decrement($isHelpful ? 'not_helpful_count' : 'helpful_count');
                    $review->increment($isHelpful ? 'helpful_count' : 'not_helpful_count');
                }
            } else {
                // No existing vote, create new one
                ReviewHelpfulness::create([
                    'review_id' => $review->id,
                    'user_id' => Auth::id(),
                    'is_helpful' => $isHelpful,
                ]);

                $review->increment($isHelpful ? 'helpful_count' : 'not_helpful_count');
            }

            // Refresh the review's vote counts
            $review->refresh();
        });
    }

    /**
     * Get statistics for a product
     *
     * @param Product $product
     * @return array
     */
    public function getStatistics(Product $product): array
    {
        return [
            'total' => $this->totalReview($product),
            'average' => $this->averageRating($product),
            'distribution' => $this->getDistributionWithPercentages($product),
        ];
    }

    /**
     * Get rating distribution with percentages
     *
     * @param Product $product
     * @return array
     */
    public function getDistributionWithPercentages(Product $product): array
    {
        $distribution = $this->ratingDistribution($product);
        $total = $this->totalReview($product);

        $result = [];
        foreach ($distribution as $rating => $count) {
            $result[$rating] = [
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100) : 0,
            ];
        }

        return $result;
    }
}

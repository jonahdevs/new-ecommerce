<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rating = fake()->numberBetween(1, 5);


        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'product_id' => Product::inRandomOrder()->first()->id,
            'order_id' => null,
            'rating' => $rating,
            'title' => $this->generateReviewTitle($rating),
            'review_text' => $this->generateReviewText($rating),
            'status' => fake()->randomElement(['approved', 'approved', 'approved', 'pending']), // 75% approved
            'is_verified_purchase' => fake()->boolean(70), // 70% verified purchases
            'helpful_count' => fake()->numberBetween(0, 50),
            'not_helpful_count' => fake()->numberBetween(0, 10),
            'moderated_by' => null,
            'moderated_at' => null,
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Generate a review title based on rating
     */
    protected function generateReviewTitle(int $rating): string
    {
        $titles = [
            5 => [
                'Excellent product!',
                'Highly recommend!',
                'Best purchase ever',
                'Amazing quality',
                'Perfect!',
                'Outstanding product',
                'Exceeded expectations',
                'Love it!',
                'Fantastic purchase',
                'Five stars all the way',
            ],
            4 => [
                'Great product',
                'Very satisfied',
                'Good quality',
                'Worth the money',
                'Pleased with purchase',
                'Solid product',
                'Happy with it',
                'Good value',
                'Recommended',
                'Nice product',
            ],
            3 => [
                'It\'s okay',
                'Average product',
                'Decent',
                'Could be better',
                'Fair quality',
                'Meets expectations',
                'Not bad',
                'Acceptable',
                'Middle of the road',
                'So-so',
            ],
            2 => [
                'Disappointed',
                'Not great',
                'Below expectations',
                'Could improve',
                'Not impressed',
                'Mediocre quality',
                'Not worth it',
                'Expected more',
                'Unsatisfied',
                'Poor quality',
            ],
            1 => [
                'Very disappointed',
                'Terrible product',
                'Don\'t buy',
                'Waste of money',
                'Awful experience',
                'Do not recommend',
                'Poor quality',
                'Complete disappointment',
                'Not worth it at all',
                'Regret buying',
            ],
        ];

        return fake()->randomElement($titles[$rating]);
    }

    /**
     * Generate review text based on rating
     */
    protected function generateReviewText(int $rating): string
    {
        $reviews = [
            5 => [
                'This product exceeded all my expectations! The quality is outstanding and it works perfectly. I\'ve been using it for a few weeks now and couldn\'t be happier. Highly recommend to anyone considering this purchase.',
                'Absolutely love this product! The build quality is excellent and it performs exactly as advertised. Customer service was also very helpful. Worth every penny!',
                'Best purchase I\'ve made in a long time. The product arrived quickly and was exactly as described. The quality is superb and it has made my life so much easier. Would definitely buy again!',
                'I am extremely satisfied with this product. It\'s well-made, durable, and performs brilliantly. The attention to detail is impressive. Can\'t recommend it enough!',
                'Outstanding quality and performance! This product has become an essential part of my daily routine. The design is sleek and modern, and it works flawlessly.',
            ],
            4 => [
                'Very good product overall. Does what it\'s supposed to do and the quality is solid. Lost one star because of minor issues with packaging, but otherwise very satisfied.',
                'Great purchase! The product works well and feels well-made. Setup was easy and I\'ve had no problems so far. Would recommend to others.',
                'I\'m quite happy with this product. It performs as expected and the quality seems good. The price point is fair for what you get. A solid choice.',
                'Good quality product that does the job well. There are a few minor improvements that could be made, but overall I\'m pleased with my purchase.',
                'Very satisfied with this product. It works great and the build quality is impressive. Delivery was prompt and everything was well-packaged.',
            ],
            3 => [
                'The product is okay, nothing special. It does what it\'s supposed to do, but I expected a bit more for the price. Average quality and performance.',
                'It\'s decent but has some drawbacks. Works as advertised but there are better options out there. Not bad, but not great either.',
                'Mixed feelings about this product. Some things are good, others could be better. It\'s acceptable but I wouldn\'t say I\'m thrilled with it.',
                'Average product. Gets the job done but nothing to write home about. Quality is acceptable but could be improved in several areas.',
                'It\'s okay for the price. Not the best quality but functional. If you\'re on a budget, it might work for you, but don\'t expect too much.',
            ],
            2 => [
                'Pretty disappointed with this purchase. The quality is not what I expected based on the description. It works, but barely. Would not recommend.',
                'Not happy with this product. It feels cheaply made and doesn\'t perform as well as advertised. Expected much better quality for the price.',
                'Below my expectations. The product has several issues and the quality is subpar. I\'ve had problems since day one. Looking for alternatives.',
                'Not impressed at all. The product is functional but the quality is poor. Feels like a waste of money. Would not buy again.',
                'Disappointing purchase. The product arrived with minor defects and doesn\'t work as smoothly as expected. Customer service was unhelpful.',
            ],
            1 => [
                'Terrible product. Complete waste of money. Poor quality, doesn\'t work properly, and customer service was no help. Do not buy this!',
                'Very disappointed. The product broke within a week of use. Cheaply made and not worth the money at all. Save yourself the trouble.',
                'Absolutely awful. Nothing like the description. Poor quality materials and terrible performance. I want my money back!',
                'Do not recommend! This is the worst purchase I\'ve made. The product is defective and customer service refused to help. Stay away!',
                'Regret buying this. It doesn\'t work as advertised and the quality is abysmal. Complete disappointment from start to finish.',
            ],
        ];

        return fake()->randomElement($reviews[$rating]);
    }

    /**
     * State for approved reviews
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * State for pending reviews
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * State for verified purchases
     */
    public function verified(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_verified_purchase' => true,
        ]);
    }

    /**
     * State for 5-star reviews
     */
    public function fiveStars(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rating' => 5,
                'title' => $this->generateReviewTitle(5),
                'review_text' => $this->generateReviewText(5),
            ];
        });
    }

    /**
     * State for 4-star reviews
     */
    public function fourStars(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rating' => 4,
                'title' => $this->generateReviewTitle(4),
                'review_text' => $this->generateReviewText(4),
            ];
        });
    }

    /**
     * State for 3-star reviews
     */
    public function threeStars(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rating' => 3,
                'title' => $this->generateReviewTitle(3),
                'review_text' => $this->generateReviewText(3),
            ];
        });
    }

    /**
     * State for 2-star reviews
     */
    public function twoStars(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rating' => 2,
                'title' => $this->generateReviewTitle(2),
                'review_text' => $this->generateReviewText(2),
            ];
        });
    }

    /**
     * State for 1-star reviews
     */
    public function oneStar(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rating' => 1,
                'title' => $this->generateReviewTitle(1),
                'review_text' => $this->generateReviewText(1),
            ];
        });
    }
}

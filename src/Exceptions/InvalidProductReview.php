<?php

namespace Larasell\Reviews\Exceptions;

use InvalidArgumentException;
use Larasell\Chronicle\HasStructuredError;
use Larasell\Chronicle\StructuredError;
use Larasell\Reviews\Models\ProductReview;

class InvalidProductReview extends InvalidArgumentException implements StructuredError
{
    use HasStructuredError;

    public static function invalidRating(int $rating): static
    {
        $min = ProductReview::MIN_RATING;
        $max = ProductReview::MAX_RATING;

        return static::structured(
            code: 'larasell-reviews.review.invalid-rating',
            message: "A rating must be between {$min} and {$max} stars, got {$rating}.",
            why: "Reviews rate a product with {$min} to {$max} stars, so other ratings can't be shown or averaged.",
            fix: "Pass a whole number from {$min} to {$max} as the rating.",
        );
    }

    public static function missingName(): static
    {
        return static::structured(
            code: 'larasell-reviews.review.missing-name',
            message: 'A review needs a name when no customer is linked.',
            why: "A review shows its reviewer's name, and without a linked customer there is no name to copy onto it.",
            fix: 'Pass a name, or link the customer who wrote the review.',
        );
    }
}

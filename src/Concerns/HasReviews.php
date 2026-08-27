<?php

namespace Larasell\Reviews\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Larasell\Reviews\Models\ProductReview;

trait HasReviews
{
    /** @return HasMany<ProductReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'product_id');
    }

    public function averageRating(): ?float
    {
        $average = $this->reviews()->avg('value');

        return $average === null ? null : (float) $average;
    }
}

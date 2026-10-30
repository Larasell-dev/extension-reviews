<?php

namespace Larasell\Reviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Larasell\Larasell\Models\Customer;
use Larasell\Larasell\Models\ModelRegistry;
use Larasell\Larasell\Models\Product;
use Larasell\Reviews\Enums\ProductReviewStatus;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $customer_id
 * @property string $name The reviewer's name, kept when their customer is deleted.
 * @property int $rating From 1 to 5 stars.
 * @property string|null $comment
 * @property ProductReviewStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Customer|null $customer
 */
class ProductReview extends Model
{
    public const int MIN_RATING = 1;

    public const int MAX_RATING = 5;

    protected $table = 'larasell_product_reviews';

    protected $guarded = [];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ProductReviewStatus::class,
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(app(ModelRegistry::class)->product->class(), 'product_id');
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(app(ModelRegistry::class)->customer->class(), 'customer_id');
    }
}

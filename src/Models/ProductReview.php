<?php

namespace Larasell\Reviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Larasell\Larasell\Models\ModelRegistry;
use Larasell\Larasell\Models\Product;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property int $value
 * @property string|null $content
 * @property Product $product
 */
class ProductReview extends Model
{
    protected $table = 'larasell_product_reviews';

    protected $guarded = [];

    protected $casts = [
        'value' => 'integer',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            app(ModelRegistry::class)->product->class(),
            'product_id',
        );
    }
}

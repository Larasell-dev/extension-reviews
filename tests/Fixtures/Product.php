<?php

namespace Larasell\Reviews\Tests\Fixtures;

use Larasell\Larasell\Models\Product as BaseProduct;
use Larasell\Reviews\Concerns\HasReviews;

class Product extends BaseProduct
{
    use HasReviews;
}

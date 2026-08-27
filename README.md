# Larasell Reviews

Product reviews for Larasell.

## Installation

```bash
composer require larasell/reviews
php artisan migrate
```

Add the review concern to your application's Product model:

```php
namespace App\Models;

use Larasell\Larasell\Models\Product as BaseProduct;
use Larasell\Reviews\Concerns\HasReviews;

class Product extends BaseProduct
{
    use HasReviews;
}
```

Configure Larasell to use that model in `config/larasell.php`:

```php
'models' => [
    'product' => App\Models\Product::class,
],
```

## Usage

```php
$product->reviews()->create([
    'name' => 'Alex',
    'value' => 5,
    'content' => 'Excellent product.',
]);

$product->reviews;
$product->averageRating(); // 5.0, or null when there are no reviews
```

<?php

use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Price;
use Larasell\Reviews\Models\ProductReview;
use Larasell\Reviews\Tests\Fixtures\Product;

it('associates reviews with a product and calculates its average rating', function () {
    $product = Product::query()->create([
        'slug' => 'desk-lamp',
        'name' => 'Desk lamp',
        'price' => Price::of(4999),
        'status' => Visibility::Visible,
    ]);

    $product->reviews()->createMany([
        ['name' => 'Alex', 'value' => 4, 'content' => 'Very good.'],
        ['name' => 'Sam', 'value' => 5, 'content' => null],
    ]);

    expect($product->reviews)->toHaveCount(2)
        ->and($product->averageRating())->toBe(4.5)
        ->and($product->reviews->first())->toBeInstanceOf(ProductReview::class)
        ->and($product->reviews->first()->product)->toBeInstanceOf(Product::class);
});

it('returns null when a product has no ratings', function () {
    $product = Product::query()->create([
        'slug' => 'empty-product',
        'name' => 'Empty product',
        'price' => Price::of(1000),
        'status' => Visibility::Visible,
    ]);

    expect($product->averageRating())->toBeNull();
});

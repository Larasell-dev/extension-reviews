<?php

declare(strict_types=1);

use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;
use Larasell\Reviews\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Architecture');

function createProduct(string $name = 'Desk lamp'): Product
{
    return Product::create(['slug' => str($name)->slug()->value(), 'name' => $name, 'price' => Price::of(4999), 'status' => Visibility::Visible]);
}

function signInAdmin(): Admin
{
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    test()->actingAs($admin, 'larasell-admin');

    return $admin;
}

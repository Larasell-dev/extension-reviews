<?php

declare(strict_types=1);

use Larasell\Admin\Models\Admin;
use Larasell\Reviews\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Architecture');

function signInAdmin(): Admin
{
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    test()->actingAs($admin, 'larasell-admin');

    return $admin;
}

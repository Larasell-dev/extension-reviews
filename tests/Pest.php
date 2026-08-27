<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Larasell\Reviews\Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

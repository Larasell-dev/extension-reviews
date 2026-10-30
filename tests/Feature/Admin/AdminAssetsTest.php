<?php

declare(strict_types=1);

use Larasell\Admin\AdminPanel;

it('loads its admin assets into the admin panel', function () {
    $public = dirname(__DIR__, 3).'/public';

    expect(AdminPanel::resolve()->extensions())->toBe([
        'larasell-reviews' => ['publicPath' => $public, 'entries' => ['resources/js/admin.ts', 'resources/css/admin.css']],
    ]);
});

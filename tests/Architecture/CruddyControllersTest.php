<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cruddy by Design — controller architecture test
|--------------------------------------------------------------------------
|
| Controllers may only contain the seven resourceful CRUD actions. Magic
| methods and constructors are permitted. Private/protected helpers are
| not actions and are ignored. A change of state, such as approving a
| review, is its own resource, e.g. ProductReviewApprovalController@store.
| Single-action controllers use __invoke.
|
*/

$cruddyAllowedMethods = ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'];
$cruddyMagicMethods = ['__construct', '__invoke', '__call', '__callStatic', '__get', '__set', '__isset', '__unset', '__toString'];

arch('controllers follow the cruddy by design pattern', function () use ($cruddyAllowedMethods, $cruddyMagicMethods) {
    $directory = dirname(__DIR__, 2).'/src/Http/Controllers';
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    $checked = 0;

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $class = 'Larasell\\Reviews\\Http\\Controllers\\'.str_replace('/', '\\', substr($file->getPathname(), strlen($directory) + 1, -4));

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if (in_array($name, $cruddyMagicMethods, true) || $method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            expect($name)->toBeIn($cruddyAllowedMethods,
                "Controller {$class} has a non-cruddy method [{$name}()]. Only cruddy actions are allowed: ".implode(', ', $cruddyAllowedMethods).'.');
        }

        $checked++;
    }

    if ($checked === 0) {
        $this->markTestSkipped('No controllers yet.');
    }
});

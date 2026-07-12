<?php

use Tests\TestCase;

$loader = require __DIR__.'/../../../../vendor/autoload.php';

$loader->addPsr4('Alexkramse\\FilamentOpenapiDocs\\', __DIR__.'/../src/');
$loader->addPsr4('Alexkramse\\FilamentOpenapiDocs\\Tests\\', __DIR__.'/');

if (! class_exists(Orchestra\Testbench\TestCase::class) && class_exists(TestCase::class)) {
    class_alias(TestCase::class, Orchestra\Testbench\TestCase::class);
}

return $loader;

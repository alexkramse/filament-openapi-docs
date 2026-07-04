<?php

$loader = require __DIR__ . '/../../../../vendor/autoload.php';

$loader->addPsr4('Kramarenko\\FilamentOpenApiDocs\\', __DIR__ . '/../src/');
$loader->addPsr4('Kramarenko\\FilamentOpenApiDocs\\Tests\\', __DIR__ . '/');

if (! class_exists(Orchestra\Testbench\TestCase::class) && class_exists(Tests\TestCase::class)) {
    class_alias(Tests\TestCase::class, Orchestra\Testbench\TestCase::class);
}

return $loader;

<?php

namespace Kramarenko\FilamentOpenApiDocs\Tests;

use Dedoc\Scramble\ScrambleServiceProvider;
use Kramarenko\FilamentOpenApiDocs\FilamentOpenApiDocsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ScrambleServiceProvider::class,
            FilamentOpenApiDocsServiceProvider::class,
        ];
    }
}

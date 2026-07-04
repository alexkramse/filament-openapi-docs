<?php

namespace Kramarenko\FilamentOpenApiDocs;

use Kramarenko\FilamentOpenApiDocs\Support\ScrambleSpecProvider;
use Kramarenko\FilamentOpenApiDocs\Support\SpecProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentOpenApiDocsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-openapi-docs')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SpecProvider::class, ScrambleSpecProvider::class);
    }
}

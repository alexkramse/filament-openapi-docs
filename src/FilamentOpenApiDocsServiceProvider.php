<?php

namespace Alexkramse\FilamentOpenapiDocs;

use Alexkramse\FilamentOpenapiDocs\Support\ScrambleSpecProvider;
use Alexkramse\FilamentOpenapiDocs\Support\SpecProvider;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
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

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('openapi-docs', __DIR__.'/../resources/css/openapi-docs.css')->loadedOnRequest(),
            AlpineComponent::make('request-snippet', __DIR__.'/../resources/js/dist/request-snippet.js'),
        ], 'alexkramse/filament-openapi-docs');
    }
}

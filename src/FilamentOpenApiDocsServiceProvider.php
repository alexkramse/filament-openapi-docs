<?php

namespace Alexkramse\FilamentOpenapiDocs;

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\Pages\OpenApiDocsPage;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiDataResolver;
use Alexkramse\FilamentOpenapiDocs\Support\ScrambleSpecProvider;
use Alexkramse\FilamentOpenapiDocs\Support\SpecProvider;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentOpenApiDocsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-openapi-docs';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SpecProvider::class, function (): SpecProvider {
            $provider = config('filament-openapi-docs.provider', 'scramble');

            if ($provider === 'scramble') {
                return app(ScrambleSpecProvider::class);
            }

            if (is_string($provider) && is_a($provider, SpecProvider::class, true)) {
                return app($provider);
            }

            return app(ScrambleSpecProvider::class);
        });

        $this->app->scoped(OpenApiDataResolver::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('openapi-docs', __DIR__.'/../resources/css/openapi-docs.css')->loadedOnRequest(),
            AlpineComponent::make('request-snippet', __DIR__.'/../resources/js/dist/request-snippet.js'),
        ], 'alexkramse/filament-openapi-docs');

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_SUB_NAVIGATION_SIDEBAR_BEFORE,
            fn (): View => view('filament-openapi-docs::sub-navigation.openapi-summary', $this->openApiSummaryData()),
            scopes: OpenApiDocsPage::class,
        );
    }

    /**
     * @return array{
     *     info: array<string, mixed>,
     *     servers: array<int, string>,
     *     endpoints: array<string, array<int, Endpoint>>,
     *     endpointCount: int,
     *     components: array<string, mixed>
     * }
     */
    private function openApiSummaryData(): array
    {
        return app(OpenApiDataResolver::class)->data();
    }
}

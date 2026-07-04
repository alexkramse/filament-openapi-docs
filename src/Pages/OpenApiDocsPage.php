<?php

namespace Kramarenko\FilamentOpenApiDocs\Pages;

use BackedEnum;
use Dedoc\Scramble\GeneratorConfig;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Kramarenko\FilamentOpenApiDocs\Support\SpecProvider;
use UnitEnum;

class OpenApiDocsPage extends Page
{
    protected static ?string $slug = 'api-docs';

    protected string $view = 'filament-openapi-docs::pages.openapi-docs';

    public function getMaxContentWidth(): Width | string | null
    {
        if (config('filament-openapi-docs.layout.full_width', true)) {
            return Width::Full;
        }

        return parent::getMaxContentWidth();
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-openapi-docs.navigation.label', 'API Docs');
    }

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return config('filament-openapi-docs.navigation.icon', 'heroicon-o-document-text');
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return config('filament-openapi-docs.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config('filament-openapi-docs.navigation.sort', 100);
    }

    /**
     * @return array{
     *     scrambleView: string,
     *     spec: array<string, mixed>,
     *     config: GeneratorConfig,
     * }
     */
    protected function getViewData(): array
    {
        $specProvider = app(SpecProvider::class);

        return [
            'scrambleView' => $specProvider->view(),
            'spec' => $specProvider->spec(),
            'config' => $specProvider->config(),
        ];
    }
}

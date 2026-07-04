<?php

use Filament\Panel;
use Kramarenko\FilamentOpenApiDocs\FilamentOpenApiDocsPlugin;
use Kramarenko\FilamentOpenApiDocs\Pages\OpenApiDocsPage;
use Kramarenko\FilamentOpenApiDocs\Tests\TestCase;

uses(TestCase::class);

it('registers the api docs page with a panel', function () {
    $panel = Panel::make()->id('admin');

    FilamentOpenApiDocsPlugin::make()->register($panel);

    expect($panel->getPages())->toContain(OpenApiDocsPage::class);
});

it('reads navigation values from config', function () {
    config()->set('filament-openapi-docs.navigation.label', 'OpenAPI');
    config()->set('filament-openapi-docs.navigation.icon', 'heroicon-o-code-bracket-square');
    config()->set('filament-openapi-docs.navigation.group', 'Engineering');
    config()->set('filament-openapi-docs.navigation.sort', 42);

    expect(OpenApiDocsPage::getNavigationLabel())->toBe('OpenAPI')
        ->and(OpenApiDocsPage::getNavigationIcon())->toBe('heroicon-o-code-bracket-square')
        ->and(OpenApiDocsPage::getNavigationGroup())->toBe('Engineering')
        ->and(OpenApiDocsPage::getNavigationSort())->toBe(42);
});

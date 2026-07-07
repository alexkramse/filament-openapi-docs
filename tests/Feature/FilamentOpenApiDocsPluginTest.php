<?php

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Illuminate\Support\Facades\File;
use Kramarenko\FilamentOpenApiDocs\FilamentOpenApiDocsPlugin;
use Kramarenko\FilamentOpenApiDocs\Pages\OpenApiDocsPage;
use Kramarenko\FilamentOpenApiDocs\Support\SpecProvider;
use Mockery as m;

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

it('uses compiled filament or package classes in blade views', function () {
    $unsupportedUtilityClasses = [
        'flex',
        'flex-col',
        'flex-wrap',
        'grid',
        'items-center',
        'items-end',
        'justify-end',
        'gap-1',
        'gap-2',
        'gap-3',
        'gap-4',
        'gap-5',
        'gap-6',
        'mt-5',
        'mb-8',
        'ml-4',
        'p-3',
        'rounded-lg',
        'border',
        'border-gray-200',
        'bg-white',
        'text-xs',
        'text-sm',
        'text-base',
        'font-medium',
        'font-mono',
        'font-semibold',
        'text-gray-500',
        'text-gray-950',
        'dark:text-white',
    ];

    $classes = collect(File::allFiles(__DIR__.'/../../resources/views'))
        ->flatMap(function (SplFileInfo $file): array {
            preg_match_all('/class=(["\'])(.*?)\1/s', $file->getContents(), $matches);

            return collect($matches[2])
                ->flatMap(fn (string $classList): array => preg_split('/\s+/', trim($classList)) ?: [])
                ->filter()
                ->all();
        })
        ->unique()
        ->values();

    expect($classes->intersect($unsupportedUtilityClasses)->values()->all())->toBe([]);
});

it('exposes endpoints through native filament sub navigation', function () {
    $provider = m::mock(SpecProvider::class);
    $provider
        ->shouldReceive('spec')
        ->once()
        ->andReturn([
            'paths' => [
                '/users' => [
                    'get' => [
                        'tags' => ['Users'],
                        'summary' => 'List users',
                    ],
                ],
                '/users/{user}' => [
                    'get' => [
                        'tags' => ['Users'],
                        'summary' => 'Show user',
                    ],
                ],
            ],
        ]);

    app()->instance(SpecProvider::class, $provider);

    $page = app(OpenApiDocsPage::class);
    $subNavigation = $page->getSubNavigation();

    expect($page::getSubNavigationPosition())->toBe(SubNavigationPosition::Start)
        ->and($subNavigation)->toHaveCount(1)
        ->and($subNavigation[0]->getLabel())->toContain('Users')
        ->and($subNavigation[0]->getItems()[0]->getLabel())->toBe('/users')
        ->and($subNavigation[0]->getItems()[0]->getUrl())->toBe('#')
        ->and($subNavigation[0]->getItems()[0]->isActive())->toBeTrue()
        ->and($subNavigation[0]->getItems()[0]->getExtraAttributes()['wire:click.prevent'])->toBe("selectEndpoint('get-users')");

    $page->selectEndpoint('get-usersuser');
    $subNavigation = $page->getSubNavigation();

    expect($page->selectedEndpointId)->toBe('get-usersuser')
        ->and($subNavigation[0]->getItems()[0]->isActive())->toBeFalse()
        ->and($subNavigation[0]->getItems()[1]->isActive())->toBeTrue();
});

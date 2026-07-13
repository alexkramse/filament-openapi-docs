<?php

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\FilamentOpenApiDocsPlugin;
use Alexkramse\FilamentOpenapiDocs\Pages\OpenApiDocsPage;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiNavigationBuilder;
use Alexkramse\FilamentOpenapiDocs\Support\SpecProvider;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Url;
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

it('uses left sub navigation by default', function () {
    expect(OpenApiDocsPage::getSubNavigationPosition())->toBe(SubNavigationPosition::Start);
});

it('can render sub navigation on the right from config', function () {
    config()->set('filament-openapi-docs.sub_navigation.position', 'right');

    expect(OpenApiDocsPage::getSubNavigationPosition())->toBe(SubNavigationPosition::End);
});

it('uses configured page title before openapi title', function () {
    config()->set('filament-openapi-docs.page.title', 'Configured Docs');

    expect(app(OpenApiDocsPage::class)->getTitle())->toBe('Configured Docs');
});

it('falls back to openapi title when configured page title is empty', function () {
    config()->set('filament-openapi-docs.page.title', '');
    bindOpenApiSpec([
        'info' => [
            'title' => 'Game API',
        ],
    ]);

    expect(app(OpenApiDocsPage::class)->getTitle())->toBe('Game API');
});

it('falls back to application name when configured and openapi titles are empty', function () {
    config()->set('app.name', 'Game Dashboard');
    config()->set('filament-openapi-docs.page.title', '');
    bindOpenApiSpec([
        'info' => [
            'title' => '',
        ],
    ]);

    expect(app(OpenApiDocsPage::class)->getTitle())->toBe('Game Dashboard');
});

it('uses laravel as the final page title fallback', function () {
    config()->set('app.name', '');
    config()->set('filament-openapi-docs.page.title', '');
    bindOpenApiSpec([
        'info' => [],
    ]);

    expect(app(OpenApiDocsPage::class)->getTitle())->toBe('Laravel');
});

it('uses configured page description before openapi description', function () {
    config()->set('filament-openapi-docs.page.description', 'Configured description');

    expect(app(OpenApiDocsPage::class)->getSubheading())->toBe('Configured description');
});

it('falls back to openapi description when configured page description is empty', function () {
    config()->set('filament-openapi-docs.page.description', '');
    bindOpenApiSpec([
        'info' => [
            'description' => 'Generated from OpenAPI.',
        ],
    ]);

    expect(app(OpenApiDocsPage::class)->getSubheading())->toBe('Generated from OpenAPI.');
});

it('uses an empty page description when configured and openapi descriptions are empty', function () {
    config()->set('filament-openapi-docs.page.description', '');
    bindOpenApiSpec([
        'info' => [
            'description' => '',
        ],
    ]);

    expect(app(OpenApiDocsPage::class)->getSubheading())->toBe('');
});

it('uses openapi version as the navigation badge', function () {
    bindOpenApiSpec([
        'info' => [
            'version' => '1.2.3',
        ],
    ]);

    expect(OpenApiDocsPage::getNavigationBadge())->toBe('1.2.3');
});

it('can use the endpoint count as the navigation badge', function () {
    config()->set('filament-openapi-docs.navigation.badge', 'count');
    bindOpenApiSpec(openApiSpecWithEndpoints());

    expect(OpenApiDocsPage::getNavigationBadge())->toBe('2');
});

it('does not render a navigation badge when configured badge is null', function () {
    config()->set('filament-openapi-docs.navigation.badge', null);

    expect(OpenApiDocsPage::getNavigationBadge())->toBeNull();
});

it('does not render a navigation badge when configured badge is unknown', function () {
    config()->set('filament-openapi-docs.navigation.badge', 'unknown');

    expect(OpenApiDocsPage::getNavigationBadge())->toBeNull();
});

it('wraps version navigation badge with configured prefix and suffix', function () {
    config()->set('filament-openapi-docs.navigation.badge_prefix', 'v');
    config()->set('filament-openapi-docs.navigation.badge_suffix', ' beta');
    bindOpenApiSpec([
        'info' => [
            'version' => '1.2.3',
        ],
    ]);

    expect(OpenApiDocsPage::getNavigationBadge())->toBe('v1.2.3 beta');
});

it('wraps endpoint count navigation badge with configured prefix and suffix', function () {
    config()->set('filament-openapi-docs.navigation.badge', 'count');
    config()->set('filament-openapi-docs.navigation.badge_prefix', '');
    config()->set('filament-openapi-docs.navigation.badge_suffix', ' endpoints');
    bindOpenApiSpec(openApiSpecWithEndpoints([
        'version' => '1.2.3',
    ]));

    expect(OpenApiDocsPage::getNavigationBadge())->toBe('2 endpoints');
});

it('does not render a version navigation badge when openapi version is empty even with configured prefix and suffix', function () {
    config()->set('filament-openapi-docs.navigation.badge_prefix', 'v');
    config()->set('filament-openapi-docs.navigation.badge_suffix', ' beta');
    bindOpenApiSpec([
        'info' => [
            'version' => '',
        ],
    ]);

    expect(OpenApiDocsPage::getNavigationBadge())->toBeNull();
});

it('stores selected endpoint in browser history', function () {
    $attribute = collect((new ReflectionProperty(OpenApiDocsPage::class, 'selectedEndpointId'))->getAttributes(Url::class))
        ->first()
        ?->newInstance();

    expect($attribute)->toBeInstanceOf(Url::class)
        ->and(invade($attribute)->as)->toBe('endpoint')
        ->and(invade($attribute)->history)->toBeTrue();
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

it('moves openapi summary from the page content to the sub navigation partial', function () {
    $pageView = file_get_contents(__DIR__.'/../../resources/views/pages/openapi-docs.blade.php');
    $summaryView = file_get_contents(__DIR__.'/../../resources/views/sub-navigation/openapi-summary.blade.php');

    expect($pageView)->not->toContain(':heading="$info[\'title\'] ?? \'API Documentation\'"')
        ->and($summaryView)->not->toContain('<x-filament::section')
        ->and($summaryView)->toContain('{{ $endpointCount }} endpoints');
});

it('registers openapi summary at the top of the endpoint sub navigation sidebar', function () {
    expect(FilamentView::hasRenderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_SIDEBAR_BEFORE, OpenApiDocsPage::class))->toBeTrue()
        ->and(FilamentView::hasRenderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_START_BEFORE, OpenApiDocsPage::class))->toBeFalse()
        ->and(FilamentView::hasRenderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_END_BEFORE, OpenApiDocsPage::class))->toBeFalse();
});

it('renders openapi summary data above endpoint sub navigation', function () {
    bindOpenApiSpec([
        'info' => [
            'title' => 'Game API',
            'description' => 'Developer documentation.',
            'version' => '1.2.3',
        ],
        'servers' => [
            ['url' => 'https://api.example.test'],
        ],
        'paths' => [
            '/users' => [
                'get' => [
                    'summary' => 'List users',
                ],
            ],
            '/health' => [
                'get' => [
                    'summary' => 'Health check',
                ],
            ],
        ],
    ]);

    $html = html_entity_decode((string) FilamentView::renderHook(
        PanelsRenderHook::PAGE_SUB_NAVIGATION_SIDEBAR_BEFORE,
        OpenApiDocsPage::class,
    ));

    expect($html)->toContain('https://api.example.test')
        ->and($html)->toContain('v1.2.3')
        ->and($html)->toContain('2 endpoints');
});

it('adds spacing between openapi summary server urls and meta badges', function () {
    $styles = file_get_contents(__DIR__.'/../../resources/css/openapi-docs.css');

    expect($styles)->toContain('.foad-openapi-summary-servers')
        ->and($styles)->toContain('margin-bottom: .75rem;');
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
                        'operationId' => 'listUsers',
                        'summary' => 'List users',
                    ],
                ],
                '/users/{user}' => [
                    'get' => [
                        'tags' => ['Users'],
                        'operationId' => 'showUser',
                        'summary' => 'Show user',
                    ],
                ],
                '/health' => [
                    'get' => [
                        'tags' => ['Users'],
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
        ->and($subNavigation[0]->getItems()[0]->getLabel())->toBe('List users')
        ->and($subNavigation[0]->getItems()[1]->getLabel())->toBe('Show user')
        ->and($subNavigation[0]->getItems()[2]->getLabel())->toBe('GET /health')
        ->and($subNavigation[0]->getItems()[0]->getBadge())->toBe('GET')
        ->and($subNavigation[0]->getItems()[0]->getBadgeColor())->toBe('success')
        ->and($subNavigation[0]->getItems()[0]->getUrl())->toBe('?endpoint=listUsers')
        ->and($subNavigation[0]->getItems()[1]->getUrl())->toBe('?endpoint=showUser')
        ->and($subNavigation[0]->getItems()[0]->isActive())->toBeTrue()
        ->and($subNavigation[0]->getItems()[0]->getExtraAttributes()['wire:click.prevent'])->toBe("selectEndpoint('listUsers')");

    $page->selectEndpoint('showUser');
    $subNavigation = $page->getSubNavigation();

    expect($page->selectedEndpointId)->toBe('showUser')
        ->and($subNavigation[0]->getItems()[0]->isActive())->toBeFalse()
        ->and($subNavigation[0]->getItems()[1]->isActive())->toBeTrue();
});

it('uses a valid url endpoint selection when building sub navigation', function () {
    $provider = m::mock(SpecProvider::class);
    $provider
        ->shouldReceive('spec')
        ->once()
        ->andReturn([
            'paths' => [
                '/users' => [
                    'get' => [
                        'tags' => ['Users'],
                        'operationId' => 'listUsers',
                        'summary' => 'List users',
                    ],
                ],
                '/users/{user}' => [
                    'get' => [
                        'tags' => ['Users'],
                        'operationId' => 'showUser',
                        'summary' => 'Show user',
                    ],
                ],
            ],
        ]);

    app()->instance(SpecProvider::class, $provider);

    $page = app(OpenApiDocsPage::class);
    $page->selectedEndpointId = 'showUser';

    $subNavigation = $page->getSubNavigation();

    expect($page->selectedEndpointId)->toBe('showUser')
        ->and($subNavigation[0]->getItems()[0]->isActive())->toBeFalse()
        ->and($subNavigation[0]->getItems()[1]->isActive())->toBeTrue();
});

it('opens only the first endpoint navigation group by default', function () {
    $navigation = app(OpenApiNavigationBuilder::class)->build([
        'Users' => [endpointForNavigation('get-users', 'GET', '/users', 'List users', ['Users'])],
        'Games' => [endpointForNavigation('get-games', 'GET', '/games', 'List games', ['Games'])],
    ]);

    expect($navigation)->toHaveCount(2)
        ->and($navigation[0]->isCollapsible())->toBeTrue()
        ->and($navigation[0]->isCollapsed())->toBeFalse()
        ->and($navigation[1]->isCollapsible())->toBeTrue()
        ->and($navigation[1]->isCollapsed())->toBeTrue();
});

it('escapes operation ids in endpoint navigation click handlers', function () {
    $navigation = app(OpenApiNavigationBuilder::class)->build([
        'Users' => [endpointForNavigation('showUser\'sProfile', 'GET', '/users/{user}', 'Show user', ['Users'])],
    ]);

    expect($navigation[0]->getItems()[0]->getExtraAttributes()['wire:click.prevent'])
        ->toBe('selectEndpoint(\'showUser\\u0027sProfile\')')
        ->and($navigation[0]->getItems()[0]->getUrl())->toBe('?endpoint=showUser%27sProfile');
});

it('normalizes persisted collapsed state for endpoint sub navigation groups', function () {
    $view = file_get_contents(__DIR__.'/../../resources/views/pages/openapi-docs.blade.php');

    expect($view)->toContain('collapsedGroups')
        ->and($view)->toContain("document.querySelectorAll('.fi-page-sub-navigation-sidebar [data-group-label]')")
        ->and($view)->toContain('labels.slice(1)');
});

function endpointForNavigation(string $id, string $method, string $path, string $summary, array $tags): Endpoint
{
    return new Endpoint(
        id: $id,
        method: $method,
        path: $path,
        summary: $summary,
        description: null,
        tags: $tags,
        parameters: [],
        requestBodies: [],
        responses: [],
        security: [],
        deprecated: false,
    );
}

function bindOpenApiSpec(array $spec): void
{
    $provider = m::mock(SpecProvider::class);
    $provider
        ->shouldReceive('spec')
        ->once()
        ->andReturn($spec);

    app()->instance(SpecProvider::class, $provider);
}

function openApiSpecWithEndpoints(array $info = []): array
{
    return [
        'info' => $info,
        'paths' => [
            '/users' => [
                'get' => [
                    'summary' => 'List users',
                ],
            ],
            '/health' => [
                'get' => [
                    'summary' => 'Health check',
                ],
            ],
        ],
    ];
}

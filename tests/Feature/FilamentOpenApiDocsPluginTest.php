<?php

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\FilamentOpenApiDocsPlugin;
use Alexkramse\FilamentOpenapiDocs\Pages\OpenApiDocsPage;
use Alexkramse\FilamentOpenapiDocs\Services\ExamplePresenter;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiDataResolver;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiNavigationBuilder;
use Alexkramse\FilamentOpenapiDocs\Services\RequestSnippetPresenter;
use Alexkramse\FilamentOpenapiDocs\Support\SpecProvider;
use Alexkramse\FilamentOpenapiDocs\Support\VersionedSpecProvider;
use Filament\Facades\Filament;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Dedoc\Scramble\Configuration\GeneratorConfigCollection;
use Livewire\Attributes\Url;
use Mockery as m;

afterEach(function () {
    Filament::setCurrentPanel(null);
    Filament::setCurrentPageConfigurationKey(null);
    app()->forgetInstance(FilamentOpenApiDocsPlugin::class);
    app()->forgetInstance(GeneratorConfigCollection::class);
});

it('registers the api docs page with a panel', function () {
    $panel = Panel::make()->id('admin');

    FilamentOpenApiDocsPlugin::make()->register($panel);

    expect($panel->getPages())->toContain(OpenApiDocsPage::class);
});

it('uses the container-resolved plugin for the default id', function () {
    $plugin = new FilamentOpenApiDocsPlugin;

    app()->instance(FilamentOpenApiDocsPlugin::class, $plugin);

    expect(FilamentOpenApiDocsPlugin::make())->toBe($plugin)
        ->and(FilamentOpenApiDocsPlugin::make(FilamentOpenApiDocsPlugin::ID))->toBe($plugin);
});

it('registers independent docs pages for distinct plugin ids', function () {
    $v1Plugin = FilamentOpenApiDocsPlugin::make()
        ->slug('developer/api-docs')
        ->navigationLabel('OpenAPI v1')
        ->versions(['v1']);
    $v2Plugin = FilamentOpenApiDocsPlugin::make('apiv2')
        ->slug('developer/api-docs-v2')
        ->navigationLabel('OpenAPI v2')
        ->versions(['v2']);

    $panel = Panel::make()
        ->id('admin')
        ->plugins([
            $v1Plugin,
            $v2Plugin,
        ]);

    expect($panel->getPageConfiguration(OpenApiDocsPage::class, FilamentOpenApiDocsPlugin::ID)?->getSlug())
        ->toBe('developer/api-docs')
        ->and($panel->getPageConfiguration(OpenApiDocsPage::class, 'apiv2')?->getSlug())
        ->toBe('developer/api-docs-v2');

    Filament::setCurrentPanel($panel);
    Filament::setCurrentPageConfigurationKey('apiv2');

    expect(FilamentOpenApiDocsPlugin::current())->toBe($v2Plugin)
        ->and(OpenApiDocsPage::getNavigationLabel())->toBe('OpenAPI v2');
});

it('does not allow an empty plugin id', function () {
    FilamentOpenApiDocsPlugin::make('');
})->throws(InvalidArgumentException::class, 'must not be empty');

it('keeps the legacy generator when no versions are configured', function () {
    $plugin = FilamentOpenApiDocsPlugin::make()->scrambleGenerator('legacy');

    expect($plugin->hasVersions())->toBeFalse()
        ->and($plugin->getDefaultVersion())->toBeNull()
        ->and($plugin->getScrambleGenerator())->toBe('legacy');
});

it('discovers named Scramble APIs when versions are not configured', function () {
    \Dedoc\Scramble\Scramble::registerApi('v1', ['api_path' => 'api/v1']);
    \Dedoc\Scramble\Scramble::registerApi('v2', ['api_path' => 'api/v2']);

    $plugin = FilamentOpenApiDocsPlugin::make();

    expect($plugin->hasVersions())->toBeTrue()
        ->and($plugin->getVersions())->toBe(['v1', 'v2'])
        ->and($plugin->getDefaultVersion())->toBe('v1');
});

it('uses the configured default api version and its isolated specification', function () {
    $panel = Panel::make()
        ->id('admin')
        ->plugin(FilamentOpenApiDocsPlugin::make()->versions(['v1', 'v2']));

    Filament::setCurrentPanel($panel);

    $provider = new class implements VersionedSpecProvider
    {
        public function config(): \Dedoc\Scramble\GeneratorConfig
        {
            return \Dedoc\Scramble\Scramble::getGeneratorConfig('default');
        }

        public function view(): string
        {
            return 'scramble::docs';
        }

        public function spec(): array
        {
            return $this->specFor('v1');
        }

        public function specFor(string $generator): array
        {
            return match ($generator) {
                'v2' => [
                    'info'  => ['version' => '2.0.0'],
                    'paths' => ['/health' => ['get' => ['operationId' => 'v2Health', 'summary' => 'V2 health']]],
                ],
                default => [
                    'info'  => ['version' => '1.0.0'],
                    'paths' => ['/profiles' => ['get' => ['operationId' => 'v1Profile', 'summary' => 'V1 profile']]],
                ],
            };
        }
    };

    app()->instance(SpecProvider::class, $provider);
    app()->forgetInstance(OpenApiDataResolver::class);

    $page = app(OpenApiDocsPage::class);
    $page->mount();

    expect($page->selectedVersion)->toBe('v1')
        ->and($page->selectedEndpointId)->toBe('v1Profile');

    $page->selectedVersion = 'v2';
    $page->updatedSelectedVersion();

    expect($page->selectedEndpointId)->toBe('v2Health')
        ->and($page->getSubNavigation()[0]->getItems()[0]->getLabel())->toBe('V2 health');
});

it('falls back to the default version when a selected version is invalid', function () {
    bindOpenApiSpec(openApiSpecWithEndpoints());

    $panel = Panel::make()
        ->id('admin')
        ->plugin(FilamentOpenApiDocsPlugin::make()->versions(['v1', 'v2']));

    Filament::setCurrentPanel($panel);

    $page = app(OpenApiDocsPage::class);
    $page->selectedVersion = 'unknown';
    $page->mount();

    expect($page->selectedVersion)->toBe('v1');
});

it('restricts a docs page to its selected version', function () {
    $plugin = FilamentOpenApiDocsPlugin::make()
        ->versions(['v1', 'v2'])
        ->version('v2');

    expect($plugin->getVersions())->toBe(['v2'])
        ->and($plugin->getDefaultVersion())->toBe('v2')
        ->and($plugin->getVersion('v1'))->toBeNull()
        ->and($plugin->getVersion('v2'))->toBe('v2');
});

it('allows a fixed docs page without a version list', function () {
    $plugin = FilamentOpenApiDocsPlugin::make()->version('v2');

    expect($plugin->hasVersions())->toBeTrue()
        ->and($plugin->getVersions())->toBe(['v2'])
        ->and($plugin->getDefaultVersion())->toBe('v2');
});

it('falls back to the fixed version when the URL requests another version', function () {
    $panel = Panel::make()
        ->id('admin')
        ->plugin(FilamentOpenApiDocsPlugin::make()->versions(['v1', 'v2'])->version('v2'));

    Filament::setCurrentPanel($panel);
    app()->instance(SpecProvider::class, new class implements VersionedSpecProvider
    {
        public function config(): \Dedoc\Scramble\GeneratorConfig
        {
            return \Dedoc\Scramble\Scramble::getGeneratorConfig('default');
        }

        public function view(): string
        {
            return 'scramble::docs';
        }

        public function spec(): array
        {
            return $this->specFor('v2');
        }

        public function specFor(string $generator): array
        {
            return [
                'paths' => [
                    '/health' => [
                        'get' => [
                            'operationId' => "{$generator}Health",
                            'summary'     => "{$generator} health",
                        ],
                    ],
                ],
            ];
        }
    });
    app()->forgetInstance(OpenApiDataResolver::class);

    $page = app(OpenApiDocsPage::class);
    $page->selectedVersion = 'v1';
    $page->mount();

    expect($page->selectedVersion)->toBe('v2')
        ->and($page->selectedEndpointId)->toBe('v2Health');
});

it('does not allow empty version aliases', function () {
    FilamentOpenApiDocsPlugin::make()->versions(['v1', '']);
})->throws(InvalidArgumentException::class, 'non-empty Scramble aliases');

it('does not allow an empty version list', function () {
    FilamentOpenApiDocsPlugin::make()->versions([]);
})->throws(InvalidArgumentException::class, 'at least one Scramble alias');

it('does not allow duplicate version aliases', function () {
    FilamentOpenApiDocsPlugin::make()->versions(['v1', 'v1']);
})->throws(InvalidArgumentException::class, 'must be unique');

it('requires the fixed version to belong to a configured version list', function () {
    FilamentOpenApiDocsPlugin::make()
        ->versions(['v1'])
        ->version('v2');
})->throws(InvalidArgumentException::class, 'must be included');

it('does not register the api docs page in production by default', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $panel = Panel::make()->id('admin');

    FilamentOpenApiDocsPlugin::make()->register($panel);

    expect($panel->getPages())->not->toContain(OpenApiDocsPage::class);
});

it('can register the api docs page in production from config', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config()->set('filament-openapi-docs.page.enabled_in_production', true);

    $panel = Panel::make()->id('admin');

    FilamentOpenApiDocsPlugin::make()->register($panel);

    expect($panel->getPages())->toContain(OpenApiDocsPage::class);
});

it('can register the api docs page in production from fluent plugin configuration', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $panel = Panel::make()->id('admin');

    FilamentOpenApiDocsPlugin::make()
        ->enabledInProduction()
        ->register($panel);

    expect($panel->getPages())->toContain(OpenApiDocsPage::class);
});

it('uses fluent production visibility before package config', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config()->set('filament-openapi-docs.page.enabled_in_production', true);

    $panel = Panel::make()->id('admin');

    FilamentOpenApiDocsPlugin::make()
        ->enabledInProduction(false)
        ->register($panel);

    expect($panel->getPages())->not->toContain(OpenApiDocsPage::class);
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

it('uses right sub navigation by default', function () {
    expect(OpenApiDocsPage::getSubNavigationPosition())->toBe(SubNavigationPosition::End);
});

it('can render sub navigation on the left from config', function () {
    config()->set('filament-openapi-docs.sub_navigation.position', 'left');

    expect(OpenApiDocsPage::getSubNavigationPosition())->toBe(SubNavigationPosition::Start);
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

    expect(OpenApiDocsPage::getNavigationBadge())->toBe('v1.2.3');
});

it('can use the endpoint count as the navigation badge', function () {
    config()->set('filament-openapi-docs.navigation.badge', 'count');
    bindOpenApiSpec(openApiSpecWithEndpoints());

    expect(OpenApiDocsPage::getNavigationBadge())->toBe('v2');
});

it('uses fluent plugin configuration before package config', function () {
    config()->set('filament-openapi-docs.navigation.label', 'Config Docs');
    config()->set('filament-openapi-docs.navigation.icon', 'heroicon-o-document-text');
    config()->set('filament-openapi-docs.navigation.group', 'Developer');
    config()->set('filament-openapi-docs.navigation.sort', 100);
    config()->set('filament-openapi-docs.navigation.badge', 'version');
    config()->set('filament-openapi-docs.navigation.badge_prefix', 'v');
    config()->set('filament-openapi-docs.navigation.badge_suffix', '');
    config()->set('filament-openapi-docs.sub_navigation.position', 'right');
    config()->set('filament-openapi-docs.layout.full_width', true);
    config()->set('filament-openapi-docs.request_samples.developer_options', false);

    bindOpenApiSpec(openApiSpecWithEndpoints([
        'version' => '1.2.3',
    ]));

    $panel = Panel::make()
        ->id('admin')
        ->plugin(
            FilamentOpenApiDocsPlugin::make()
                ->navigationLabel('OpenAPI')
                ->navigationIcon('heroicon-o-code-bracket-square')
                ->navigationGroup('Engineering')
                ->navigationSort(42)
                ->navigationBadge('count')
                ->navigationBadgePrefix('')
                ->navigationBadgeSuffix(' endpoints')
                ->subNavigationPosition('left')
                ->fullWidth(false)
                ->developerOptions(),
        );

    Filament::setCurrentPanel($panel);
    $requestData = app(RequestSnippetPresenter::class)->present(endpointForNavigation('get-users', 'GET', '/users', 'List users', ['Users']));

    expect(OpenApiDocsPage::getNavigationLabel())->toBe('OpenAPI')
        ->and(OpenApiDocsPage::getNavigationIcon())->toBe('heroicon-o-code-bracket-square')
        ->and(OpenApiDocsPage::getNavigationGroup())->toBe('Engineering')
        ->and(OpenApiDocsPage::getNavigationSort())->toBe(42)
        ->and(OpenApiDocsPage::getNavigationBadge())->toBe('2 endpoints')
        ->and(OpenApiDocsPage::getSubNavigationPosition())->toBe(SubNavigationPosition::Start)
        ->and(app(OpenApiDocsPage::class)->getMaxContentWidth())->toBeNull()
        ->and($requestData['hasDeveloperOptions'])->toBeTrue();
});

it('controls developer options from config', function () {
    $endpoint = endpointForNavigation('get-users', 'GET', '/users', 'List users', ['Users']);

    expect(app(RequestSnippetPresenter::class)->present($endpoint)['hasDeveloperOptions'])->toBeFalse();

    config()->set('filament-openapi-docs.request_samples.developer_options', true);

    expect(app(RequestSnippetPresenter::class)->present($endpoint)['hasDeveloperOptions'])->toBeTrue();
});

it('can disable the navigation badge from fluent plugin configuration', function () {
    bindOpenApiSpec([
        'info' => [
            'version' => '1.2.3',
        ],
    ]);

    $panel = Panel::make()
        ->id('admin')
        ->plugin(FilamentOpenApiDocsPlugin::make()->navigationBadge(null));

    Filament::setCurrentPanel($panel);

    expect(OpenApiDocsPage::getNavigationBadge())->toBeNull();
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

it('registers package assets for lazy loading', function () {
    $serviceProvider = file_get_contents(__DIR__.'/../../src/FilamentOpenApiDocsServiceProvider.php');
    $pageView = file_get_contents(__DIR__.'/../../resources/views/openapi-docs.blade.php');

    expect($serviceProvider)->toContain("Css::make('openapi-docs'")
        ->and($serviceProvider)->toContain('->loadedOnRequest()')
        ->and($serviceProvider)->toContain("AlpineComponent::make('request-snippet'")
        ->and($serviceProvider)->toContain("'alexkramse/filament-openapi-docs'")
        ->and($pageView)->toContain("FilamentAsset::getStyleHref('openapi-docs', package: 'alexkramse/filament-openapi-docs')")
        ->and($pageView)->toContain("FilamentAsset::getAlpineComponentSrc('request-snippet', 'alexkramse/filament-openapi-docs')");
});

it('renders openapi summary data above endpoint sub navigation', function () {
    bindOpenApiSpec([
        'info' => [
            'title'       => 'Game API',
            'description' => 'Developer documentation.',
            'version'     => '1.2.3',
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
        ->and($html)->toContain('Click to copy: https:\\/\\/api.example.test')
        ->and($html)->toContain('async copyToClipboard(text)')
        ->and($html)->toContain('await navigator.clipboard.writeText(text)')
        ->and($html)->toContain('window.clearTimeout(this.copyTimeout)')
        ->and($html)->toContain('new FilamentNotification()')
        ->and($html)->toContain('Copied to clipboard.')
        ->and($html)->toContain('Copy failed.')
        ->and($html)->toContain('.success()')
        ->and($html)->toContain('.danger()')
        ->and($html)->toContain('x-on:keydown.enter.prevent="$el.click()"')
        ->and($html)->toContain('x-on:keydown.space.prevent="$el.click()"')
        ->and($html)->not->toContain('async copy(server)')
        ->and($html)->not->toContain('textcommit')
        ->and($html)->not->toContain('&#039;')
        ->and($html)->not->toContain('=&gt;')
        ->and($html)->toContain('v1.2.3')
        ->and($html)->toContain('2 endpoints');
});

it('labels version selector options from Scramble configuration', function () {
    \Dedoc\Scramble\Scramble::registerApi('labelled-v1', [
        'api_path' => 'api/v1',
        'info'     => ['version' => '1.0.0'],
    ]);
    \Dedoc\Scramble\Scramble::registerApi('fallback-v2', [
        'api_path' => 'api/v2',
        'info'     => ['version' => null],
    ]);

    $panel = Panel::make()
        ->id('admin')
        ->plugin(FilamentOpenApiDocsPlugin::make()->versions(['labelled-v1', 'fallback-v2']));

    Filament::setCurrentPanel($panel);
    app()->instance(SpecProvider::class, new class implements VersionedSpecProvider
    {
        public function config(): \Dedoc\Scramble\GeneratorConfig
        {
            return \Dedoc\Scramble\Scramble::getGeneratorConfig('default');
        }

        public function view(): string
        {
            return 'scramble::docs';
        }

        public function spec(): array
        {
            return $this->specFor('labelled-v1');
        }

        public function specFor(string $generator): array
        {
            return [
                'info'  => ['version' => '1.0.0'],
                'paths' => [],
            ];
        }
    });
    app()->forgetInstance(OpenApiDataResolver::class);

    $html = html_entity_decode((string) FilamentView::renderHook(
        PanelsRenderHook::PAGE_SUB_NAVIGATION_SIDEBAR_BEFORE,
        OpenApiDocsPage::class,
    ));

    expect($html)->toContain('1.0.0 (api/v1)')
        ->and($html)->toContain('fallback-v2 (api/v2)')
        ->and($html)->not->toContain('v1.0.0');
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
                        'tags'        => ['Users'],
                        'operationId' => 'listUsers',
                        'summary'     => 'List users',
                    ],
                ],
                '/users/{user}' => [
                    'get' => [
                        'tags'        => ['Users'],
                        'operationId' => 'showUser',
                        'summary'     => 'Show user',
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

    expect($page::getSubNavigationPosition())->toBe(SubNavigationPosition::End)
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

it('preserves openapi endpoint group order in native filament sub navigation', function () {
    $provider = m::mock(SpecProvider::class);
    $provider
        ->shouldReceive('spec')
        ->once()
        ->andReturn([
            'paths' => [
                '/feedback' => [
                    'post' => [
                        'tags'        => ['Feedback'],
                        'operationId' => 'createFeedback',
                        'summary'     => 'Create feedback',
                    ],
                ],
                '/accounts' => [
                    'get' => [
                        'tags'        => ['Accounts'],
                        'operationId' => 'listAccounts',
                        'summary'     => 'List accounts',
                    ],
                ],
                '/games' => [
                    'get' => [
                        'tags'        => ['Games'],
                        'operationId' => 'listGames',
                        'summary'     => 'List games',
                    ],
                ],
            ],
        ]);

    app()->instance(SpecProvider::class, $provider);

    $subNavigation = app(OpenApiDocsPage::class)->getSubNavigation();

    expect($subNavigation)->toHaveCount(3)
        ->and($subNavigation[0]->getLabel())->toContain('Feedback')
        ->and($subNavigation[1]->getLabel())->toContain('Accounts')
        ->and($subNavigation[2]->getLabel())->toContain('Games');
});

it('caches parsed openapi data for the current request', function () {
    $provider = m::mock(SpecProvider::class);
    $provider
        ->shouldReceive('spec')
        ->once()
        ->andReturn(openApiSpecWithEndpoints());

    app()->instance(SpecProvider::class, $provider);
    app()->forgetInstance(OpenApiDataResolver::class);

    $resolver = app(OpenApiDataResolver::class);

    expect($resolver->data())->toBe($resolver->data());
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
                        'tags'        => ['Users'],
                        'operationId' => 'listUsers',
                        'summary'     => 'List users',
                    ],
                ],
                '/users/{user}' => [
                    'get' => [
                        'tags'        => ['Users'],
                        'operationId' => 'showUser',
                        'summary'     => 'Show user',
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
    $view = file_get_contents(__DIR__.'/../../resources/views/openapi-docs.blade.php');

    expect($view)->toContain('collapsedGroups')
        ->and($view)->toContain('document.querySelectorAll')
        ->and($view)->toContain("'.fi-page-sub-navigation-sidebar [data-group-label]'")
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

function renderPluginOpenApiDocsEndpoint(Endpoint $endpoint, array $servers = [], array $components = []): string
{
    $examplePresenter = app(ExamplePresenter::class);
    $requestData = app(RequestSnippetPresenter::class)->present($endpoint, $servers, $components);

    return view('filament-openapi-docs::openapi-docs.info', [
        'endpoint'          => $endpoint,
        'documentedServers' => $servers,
    ])->render()
        .view('filament-openapi-docs::openapi-docs.request', [
            'endpoint'         => $endpoint,
            'components'       => $components,
            'examplePresenter' => $examplePresenter,
            'requestData'      => $requestData,
        ])->render()
        .view('filament-openapi-docs::openapi-docs.http-snippet', [
            'requestData' => $requestData,
        ])->render()
        .view('filament-openapi-docs::openapi-docs.response', [
            'endpoint'         => $endpoint,
            'schemaComponents' => $components,
            'examplePresenter' => $examplePresenter,
        ])->render();
}

function bindOpenApiSpec(array $spec): void
{
    $provider = m::mock(SpecProvider::class);
    $provider
        ->shouldReceive('spec')
        ->once()
        ->andReturn($spec);

    app()->instance(SpecProvider::class, $provider);
    app()->forgetInstance(OpenApiDataResolver::class);
}

function openApiSpecWithEndpoints(array $info = []): array
{
    return [
        'info'  => $info,
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

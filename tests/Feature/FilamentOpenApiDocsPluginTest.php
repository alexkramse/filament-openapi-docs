<?php

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\FilamentOpenApiDocsPlugin;
use Alexkramse\FilamentOpenapiDocs\FilamentOpenApiDocsServiceProvider;
use Alexkramse\FilamentOpenapiDocs\Pages\OpenApiDocsPage;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiDataResolver;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiNavigationBuilder;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiParser;
use Alexkramse\FilamentOpenapiDocs\Services\RequestSnippetPresenter;
use Alexkramse\FilamentOpenapiDocs\Support\SpecProvider;
use Filament\Facades\Filament;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Livewire\Attributes\Url;
use Mockery as m;

afterEach(function () {
    Filament::setCurrentPanel(null);
});

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
                ->fullWidth(false),
        );

    Filament::setCurrentPanel($panel);

    expect(OpenApiDocsPage::getNavigationLabel())->toBe('OpenAPI')
        ->and(OpenApiDocsPage::getNavigationIcon())->toBe('heroicon-o-code-bracket-square')
        ->and(OpenApiDocsPage::getNavigationGroup())->toBe('Engineering')
        ->and(OpenApiDocsPage::getNavigationSort())->toBe(42)
        ->and(OpenApiDocsPage::getNavigationBadge())->toBe('2 endpoints')
        ->and(OpenApiDocsPage::getSubNavigationPosition())->toBe(SubNavigationPosition::Start)
        ->and(app(OpenApiDocsPage::class)->getMaxContentWidth())->toBeNull();
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
    $pageView = file_get_contents(__DIR__.'/../../resources/views/pages/openapi-docs.blade.php');
    $endpointView = file_get_contents(__DIR__.'/../../resources/views/components/endpoint.blade.php');

    expect($serviceProvider)->toContain("Css::make('openapi-docs'")
        ->and($serviceProvider)->toContain('->loadedOnRequest()')
        ->and($serviceProvider)->toContain("AlpineComponent::make('request-snippet'")
        ->and($serviceProvider)->toContain("'alexkramse/filament-openapi-docs'")
        ->and($pageView)->toContain("FilamentAsset::getStyleHref('openapi-docs', package: 'alexkramse/filament-openapi-docs')")
        ->and($endpointView)->toContain("FilamentAsset::getAlpineComponentSrc('request-snippet', 'alexkramse/filament-openapi-docs')");
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
        ->and($summaryView)->toContain("__('filament-openapi-docs::ui.meta.endpoints'");
});

it('registers openapi summary at the top of the endpoint sub navigation sidebar', function () {
    expect(FilamentView::hasRenderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_SIDEBAR_BEFORE, OpenApiDocsPage::class))->toBeTrue()
        ->and(FilamentView::hasRenderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_START_BEFORE, OpenApiDocsPage::class))->toBeFalse()
        ->and(FilamentView::hasRenderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_END_BEFORE, OpenApiDocsPage::class))->toBeFalse();
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

it('registers package translations for publishing', function () {
    $paths = ServiceProvider::pathsToPublish(
        FilamentOpenApiDocsServiceProvider::class,
        'filament-openapi-docs-translations',
    );

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('resources/lang')
        ->and(reset($paths))->toEndWith('lang/vendor/filament-openapi-docs');
});

it('renders package ui strings in the active locale', function () {
    app()->setLocale('uk');

    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.1.0',
        'info'    => [
            'title'   => 'Game API',
            'version' => '1.0.0',
        ],
        'servers' => [
            ['url' => 'https://api.example.test'],
        ],
        'components' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type'   => 'http',
                    'scheme' => 'bearer',
                ],
            ],
        ],
        'security' => [
            ['bearerAuth' => []],
        ],
        'paths' => [
            '/users/{user}' => [
                'post' => [
                    'tags'       => ['Users'],
                    'summary'    => 'Create user',
                    'parameters' => [
                        [
                            'name'     => 'user',
                            'in'       => 'path',
                            'required' => true,
                            'schema'   => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => '',
                            'content'     => [],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $endpoint = $parsed['endpoints']['Users'][0];
    $html = html_entity_decode(view('filament-openapi-docs::components.endpoint', [
        'endpoint'   => $endpoint,
        'servers'    => $parsed['servers'],
        'components' => $parsed['components'],
    ])->render());

    expect($html)->toContain('Запит')
        ->and($html)->toContain('Режим надсилання')
        ->and($html)->toContain('Режим розробника')
        ->and($html)->toContain('Безпека')
        ->and($html)->toContain('Тіло')
        ->and($html)->toContain('x-on:click="copyBody()"')
        ->and($html)->toContain('Відповіді');
});

it('includes localized request snippet runtime messages', function () {
    app()->setLocale('uk');

    $endpoint = new Endpoint(
        id: 'create-user',
        method: 'POST',
        path: '/users',
        summary: 'Create user',
        description: null,
        tags: ['Users'],
        parameters: [],
        requestBodies: [
            [
                'contentType' => 'application/json',
                'schema'      => ['type' => 'object'],
                'examples'    => [],
            ],
        ],
        responses: [],
        security: [],
        deprecated: false,
    );

    $requestData = app(RequestSnippetPresenter::class)->present($endpoint, ['https://api.example.test']);

    expect($requestData['messages']['copiedToClipboard'])->toBe('Скопійовано в буфер обміну.')
        ->and($requestData['messages']['copyFailed'])->toBe('Не вдалося скопіювати.')
        ->and($requestData['messages']['responseStatusBadge'])->toBe('Статус: :status')
        ->and($requestData['messages']['responseTypeBadge'])->toBe('Тип: :type')
        ->and($requestData['messages']['jsonBeforeSending'])->toBe('Перед надсиланням тіло має бути коректним JSON.')
        ->and($requestData['messages']['jsonBeforeFormatting'])->toBe('Перед форматуванням тіло має бути коректним JSON.')
        ->and($requestData['messages']['unableToSendRequest'])->toBe('Не вдалося надіслати цей запит.')
        ->and($requestData['messages']['invalidHeaderName'])->toBe('Некоректна назва заголовка: :name');
});

it('falls back to the configured fallback locale for package translations', function () {
    app()->setLocale('pl');
    app('translator')->setFallback('de');

    expect(__('filament-openapi-docs::ui.actions.send_api_request'))->toBe('API-Anfrage senden');
});

it('documents translation publishing and updates', function () {
    $readme = file_get_contents(__DIR__.'/../../README.md');

    expect($readme)->toContain('## Translations')
        ->and($readme)->toContain('English (`en`), Ukrainian (`uk`), German (`de`), Spanish (`es`), and French (`fr`)')
        ->and($readme)->toContain('php artisan vendor:publish --tag=filament-openapi-docs-translations')
        ->and($readme)->toContain('lang/vendor/filament-openapi-docs/{locale}/ui.php')
        ->and($readme)->toContain('copy the structure from `resources/lang/en/ui.php`')
        ->and($readme)->toContain('compare the newest package `resources/lang/en/ui.php`');
});

it('adds spacing between openapi summary server urls and meta badges', function () {
    $styles = file_get_contents(__DIR__.'/../../resources/css/openapi-docs.css');
    $bodyView = file_get_contents(__DIR__.'/../../resources/views/components/request-snippet/body.blade.php');
    $headersView = file_get_contents(__DIR__.'/../../resources/views/components/request-snippet/headers.blade.php');
    $requestSnippetView = file_get_contents(__DIR__.'/../../resources/views/components/request-snippet.blade.php');
    $sampleView = file_get_contents(__DIR__.'/../../resources/views/components/sample.blade.php');
    $endpointView = file_get_contents(__DIR__.'/../../resources/views/components/endpoint.blade.php');
    $readRequestView = file_get_contents(__DIR__.'/../../resources/views/components/endpoint/request/read.blade.php');
    $sendRequestView = file_get_contents(__DIR__.'/../../resources/views/components/endpoint/request/send.blade.php');

    expect($styles)->toContain('.foad-openapi-summary-servers')
        ->and($styles)->toContain('gap: 0.375rem;')
        ->and($styles)->toContain('.foad-openapi-summary-meta')
        ->and($styles)->toContain('.foad-copyable-badge')
        ->and($styles)->toContain('cursor: pointer;')
        ->and($styles)->toContain('user-select: none;')
        ->and($styles)->toContain('.foad-openapi-docs-page .fi-section-content')
        ->and($styles)->toContain('.foad-response-status-badge')
        ->and($styles)->toContain('.foad-response-status-badge[data-color="success"]')
        ->and($styles)->toContain('.foad-response-status-badge[data-color="warning"]')
        ->and($styles)->toContain('.foad-response-status-badge[data-color="danger"]')
        ->and($styles)->toContain('.foad-stack')
        ->and($styles)->toContain('.foad-send-layout')
        ->and($styles)->toContain('.foad-send-layout > .foad-stack')
        ->and($styles)->toContain('align-content: start;')
        ->and($styles)->toContain('align-items: stretch;')
        ->and($styles)->toContain('.foad-send-layout > .foad-stack:has(.foad-try-textarea)')
        ->and($styles)->toContain('.foad-send-layout > .foad-stack:has(.foad-try-textarea) .foad-try-textarea')
        ->and($styles)->toContain('grid-template-rows: minmax(0, 1fr) auto;')
        ->and($styles)->toContain('grid-template-rows: auto minmax(0, 1fr) auto;')
        ->and($styles)->toContain('field-sizing: content;')
        ->and($styles)->toContain('height: 100%;')
        ->and($styles)->toContain('min-height: 3rem;')
        ->and($styles)->toContain('.foad-body-toolbar')
        ->and($styles)->toContain('justify-content: space-between;')
        ->and($styles)->toContain('.foad-json-editor')
        ->and($styles)->toContain('box-sizing: border-box;')
        ->and($styles)->toContain('inline-size: 100%;')
        ->and($styles)->toContain('.foad-json-editor-highlight.foad-sample-code')
        ->and($styles)->toContain('.foad-json-editor-textarea')
        ->and($styles)->toContain('caret-color: var(--gray-950);')
        ->and($styles)->toContain('color: transparent;')
        ->and($styles)->toContain('field-sizing: fixed;')
        ->and($styles)->toContain('max-width: 100%;')
        ->and($styles)->toContain('min-width: 0;')
        ->and($styles)->toContain('overflow: hidden;')
        ->and($styles)->toContain('overflow: auto;')
        ->and($styles)->toContain('overflow-x: auto;')
        ->and($styles)->toContain('overflow-y: auto;')
        ->and($styles)->toContain('white-space: pre;')
        ->and($styles)->toContain('word-break: normal;')
        ->and($styles)->not->toContain(".foad-json-editor-highlight {\n  background: white;")
        ->and($styles)->not->toContain(".foad-json-editor-textarea {\n  background: transparent;\n  box-sizing: border-box;\n  caret-color: var(--gray-950);\n  color: transparent;\n  min-width: max-content;")
        ->and($styles)->toContain('.foad-header-row')
        ->and($styles)->toContain('grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;')
        ->and($styles)->toContain('.foad-header-row > *')
        ->and($styles)->toContain('.foad-send-actions')
        ->and($styles)->toContain('.foad-sample')
        ->and($styles)->toContain('.foad-sample-section')
        ->and($styles)->toContain('.foad-sample-section > .fi-section-content-ctn > .fi-section-content')
        ->and($styles)->toContain('.foad-sample-scroll')
        ->and($styles)->toContain('inline-size: 100%;')
        ->and($styles)->toContain('width: 100%;')
        ->and($styles)->toContain('.foad-sample-code')
        ->and($styles)->toContain('min-width: 0;')
        ->and($styles)->toContain('min-width: max-content;')
        ->and($styles)->toContain('overflow-x: auto;')
        ->and($styles)->toContain('overflow-y: hidden;')
        ->and($endpointView)->toContain('class="foad-sample-section"')
        ->and($readRequestView)->toContain('$requestData[\'mediaHeaders\'] !== [] || $requestData[\'headerParameters\'] !== []')
        ->and($readRequestView)->toContain('ui.labels.headers')
        ->and($readRequestView)->not->toContain('ui.labels.media_headers')
        ->and($bodyView)->toContain('class="foad-body-toolbar"')
        ->and($bodyView)->toContain('class="foad-json-editor"')
        ->and($bodyView)->toContain('icon="heroicon-m-document-duplicate"')
        ->and($bodyView)->toContain('x-on:click="copyBody()"')
        ->and($bodyView)->not->toContain('format_json')
        ->and($bodyView)->toContain('x-html="highlightedBodyText"')
        ->and($bodyView)->toContain('x-on:input.debounce.500ms="formatJsonBody(false, $event.target.value)"')
        ->and($bodyView)->toContain('x-on:blur="formatJsonBody(true, $event.target.value)"')
        ->and($bodyView)->toContain('x-on:scroll="syncBodyEditorScroll($event)"')
        ->and($bodyView)->toContain('wrap="off"')
        ->and($bodyView)->toContain('class="foad-try-textarea foad-json-editor-textarea"')
        ->and($headersView)->toContain('x-for="parameter in mediaHeaderParameters"')
        ->and($headersView)->toContain('x-bind:key="`media-header-${parameter.name}`"')
        ->and($headersView)->toContain('x-bind:disabled="!developerMode"')
        ->and($requestSnippetView)->toContain('class="foad-sample-scroll"')
        ->and($sampleView)->toContain('class="foad-sample-scroll"')
        ->and($sendRequestView)->toContain('class="fi-grid foad-send-layout md:fi-grid-cols"')
        ->and($sendRequestView)->toContain('--cols-default: repeat(1, minmax(0, 1fr));')
        ->and($sendRequestView)->toContain('--cols-md: repeat(2, minmax(0, 1fr));')
        ->and($sendRequestView)->not->toContain('request-snippet.media-headers')
        ->and($sendRequestView)->toContain('class="foad-sample-scroll foad-response-code"')
        ->and($sendRequestView)->toContain('class="foad-body-toolbar"')
        ->and($sendRequestView)->toContain('x-bind:data-color="responseStatusColor(response.status)"')
        ->and($sendRequestView)->toContain('x-text="response.status"')
        ->and($sendRequestView)->toContain('x-text="response.contentType"')
        ->and($sendRequestView)->toContain('icon="heroicon-m-document-duplicate"')
        ->and($sendRequestView)->toContain('x-on:click="copyResponseBody()"')
        ->and($sendRequestView)->toContain('x-bind:class="`language-${responsePrismLanguage}`"')
        ->and($sendRequestView)->toContain('x-html="highlightedResponseBody"')
        ->and($sendRequestView)->not->toContain('x-text="response.body"')
        ->and($styles)->not->toContain('width: 50%;')
        ->and($sendRequestView)->not->toContain('TODO')
        ->and($sendRequestView)->not->toContain('HttpStatus::color($status)')
        ->and($sendRequestView)->not->toContain('{{ response.status }}');
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
    $view = file_get_contents(__DIR__.'/../../resources/views/pages/openapi-docs.blade.php');

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

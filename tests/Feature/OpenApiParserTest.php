<?php

use Kramarenko\FilamentOpenApiDocs\DTO\Endpoint;
use Kramarenko\FilamentOpenApiDocs\Services\OpenApiNavigationBuilder;
use Kramarenko\FilamentOpenApiDocs\Services\OpenApiParser;
use Kramarenko\FilamentOpenApiDocs\Services\RequestSnippetPresenter;

it('parses openapi paths into grouped endpoints', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'info' => [
            'title' => 'Game API',
            'version' => '1.0.0',
        ],
        'servers' => [
            ['url' => 'https://example.test/api'],
        ],
        'paths' => [
            '/users/{user}' => [
                'parameters' => [
                    [
                        'name' => 'user',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                    ],
                ],
                'get' => [
                    'tags' => ['Users'],
                    'summary' => 'Show user',
                    'parameters' => [
                        [
                            'name' => 'include',
                            'in' => 'query',
                            'schema' => ['type' => 'string', 'example' => 'profile'],
                            'description' => 'Relations to include',
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => ['type' => 'integer'],
                                        ],
                                    ],
                                    'examples' => [
                                        'success' => [
                                            'summary' => 'Successful response',
                                            'value' => ['id' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Users'],
                    'summary' => 'Create user',
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name'],
                                ],
                                'example' => [
                                    'name' => 'Jane Doe',
                                ],
                            ],
                            'application/x-www-form-urlencoded' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Created'],
                    ],
                ],
            ],
        ],
    ]);

    expect($parsed['info']['title'])->toBe('Game API')
        ->and($parsed['servers'])->toBe(['https://example.test/api'])
        ->and($parsed['endpointCount'])->toBe(2)
        ->and($parsed['endpoints'])->toHaveKey('Users');

    $endpoint = $parsed['endpoints']['Users'][0];

    expect($endpoint)->toBeInstanceOf(Endpoint::class)
        ->and($endpoint->method)->toBe('GET')
        ->and($endpoint->path)->toBe('/users/{user}')
        ->and($endpoint->parameters)->toHaveCount(2)
        ->and($endpoint->parameters[1]['schema']['example'])->toBe('profile')
        ->and($endpoint->responses['200']['content']['application/json']['schema']['type'])->toBe('object')
        ->and($endpoint->responses['200']['content']['application/json']['examples']['success']['value']['id'])->toBe(1);

    expect($parsed['endpoints']['Users'][1]->requestBodies)->toHaveCount(2)
        ->and($parsed['endpoints']['Users'][1]->requestBodies[0]['contentType'])->toBe('application/json')
        ->and($parsed['endpoints']['Users'][1]->requestBodies[0]['example']['name'])->toBe('Jane Doe')
        ->and($parsed['endpoints']['Users'][1]->requestBodies[1]['contentType'])->toBe('application/x-www-form-urlencoded');
});

it('renders native endpoint markup without the scramble view include', function () {
    $endpoint = new Endpoint(
        id: 'get-users',
        method: 'GET',
        path: '/users',
        summary: 'List users',
        description: 'Returns users',
        tags: ['Users'],
        parameters: [],
        requestBodies: [],
        responses: [
            '200' => [
                'description' => 'OK',
                'content' => [],
            ],
        ],
        security: [],
        deprecated: false,
    );

    $html = view('filament-openapi-docs::components.endpoint', [
        'endpoint' => $endpoint,
    ])->render();

    expect(file_get_contents(__DIR__.'/../../resources/views/pages/openapi-docs.blade.php'))->not->toContain('scrambleView')
        ->and(file_get_contents(__DIR__.'/../../resources/views/pages/openapi-docs.blade.php'))->toContain('$selectedEndpoint')
        ->and(file_get_contents(__DIR__.'/../../resources/views/pages/openapi-docs.blade.php'))->not->toContain('x-show="selectedEndpoint ===')
        ->and($html)->not->toContain('fi-tabs')
        ->and($html)->not->toContain('x-show="activeTab')
        ->and($html)->toContain('/users')
        ->and($html)->toContain('List users')
        ->and($html)->toContain('No request data documented.')
        ->and($html)->toContain('Query parameters')
        ->and($html)->toContain('Responses')
        ->and($html)->toContain('fi-section')
        ->and($html)->toContain('fi-badge')
        ->and($html)->not->toContain('scramble::docs');
});

it('renders schemas as structured fields instead of raw json', function () {
    $html = view('filament-openapi-docs::components.schema', [
        'schema' => [
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Display name',
                    'example' => 'Jane Doe',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'disabled'],
                ],
                'profile' => [
                    'type' => 'object',
                    'properties' => [
                        'age' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ],
        ],
    ])->render();

    expect($html)->toContain('name')
        ->and($html)->toContain('Required')
        ->and($html)->toContain('Optional')
        ->and($html)->toContain('string')
        ->and($html)->toContain('Display name')
        ->and($html)->toContain('active')
        ->and($html)->toContain('Allowed')
        ->and($html)->toContain('Example')
        ->and($html)->toContain('Jane Doe')
        ->and($html)->toContain('foad-schema-tree')
        ->and($html)->toContain('foad-property-row')
        ->and($html)->toContain('foad-property-connector')
        ->and($html)->toContain('foad-property-toggle-icon-collapsed')
        ->and($html)->toContain('foad-property-toggle-icon-open')
        ->and($html)->toContain('<details')
        ->and($html)->toContain('profile')
        ->and($html)->toContain('age')
        ->and($html)->not->toContain(' open')
        ->and($html)->not->toContain('fi-input')
        ->and($html)->not->toContain('fi-collapsible')
        ->and($html)->not->toContain('"properties"')
        ->and($html)->not->toContain('&quot;properties&quot;');
});

it('renders path parameters inside try it after headers and before query parameters', function () {
    $endpoint = new Endpoint(
        id: 'get-usersuser',
        method: 'GET',
        path: '/users/{user}',
        summary: 'Show user',
        description: null,
        tags: ['Users'],
        parameters: [
            [
                'name' => 'user',
                'in' => 'path',
                'type' => 'integer',
                'required' => true,
                'description' => null,
                'schema' => ['type' => 'integer', 'example' => 5],
                'examples' => [],
            ],
            [
                'name' => 'include',
                'in' => 'query',
                'type' => 'string',
                'required' => false,
                'description' => null,
                'schema' => ['type' => 'string', 'default' => 'profile'],
                'examples' => [],
            ],
        ],
        requestBodies: [],
        responses: [],
        security: [],
        deprecated: false,
    );

    $html = view('filament-openapi-docs::components.endpoint', [
        'endpoint' => $endpoint,
    ])->render();

    expect($html)->toContain('Try it')
        ->and($html)->toContain('Path parameters')
        ->and($html)->toContain('Query parameters')
        ->and(strpos($html, 'Try it'))->toBeLessThan(strpos($html, 'Path parameters'))
        ->and(strpos($html, 'Headers'))->toBeLessThan(strpos($html, 'Path parameters'))
        ->and(strpos($html, 'Path parameters'))->toBeLessThan(strpos($html, 'Query parameters'));
});

it('builds endpoint navigation for native filament sub navigation', function () {
    $endpoint = new Endpoint(
        id: 'get-users',
        method: 'GET',
        path: '/users',
        summary: 'List users',
        description: null,
        tags: ['Users'],
        parameters: [],
        requestBodies: [],
        responses: [],
        security: [],
        deprecated: false,
    );

    $navigation = app(OpenApiNavigationBuilder::class)->build(['Users' => [$endpoint]]);

    $page = file_get_contents(__DIR__.'/../../resources/views/pages/openapi-docs.blade.php');

    expect($page)->not->toContain('navigationCollapsed')
        ->and($page)->not->toContain('Toggle endpoint navigation')
        ->and($page)->not->toContain('foad-docs-shell')
        ->and($page)->not->toContain('foad-endpoint-sidebar')
        ->and($page)->not->toContain('foad-docs-content')
        ->and($page)->not->toContain('width: 20rem')
        ->and($page)->not->toContain('width: 4rem')
        ->and($page)->not->toContain('<aside')
        ->and($page)->toContain('position: sticky')
        ->and($page)->toContain('foad-openapi-docs-page')
        ->and($page)->not->toContain('slide-over')
        ->and($page)->not->toContain('x-filament::modal')
        ->and($navigation[0]->getLabel())->toContain('Users')
        ->and($navigation[0]->getItems()[0]->getUrl())->toBe('#')
        ->and($navigation[0]->getItems()[0]->getBadge())->toBe('GET')
        ->and($navigation[0]->getItems()[0]->getBadgeColor())->toBe('success')
        ->and($navigation[0]->getItems()[0]->getExtraAttributes()['wire:click.prevent'])->toBe("selectEndpoint('get-users')");
});

it('renders request samples and response examples for documented media types', function () {
    $endpoint = new Endpoint(
        id: 'post-users',
        method: 'POST',
        path: '/users',
        summary: 'Create user',
        description: null,
        tags: ['Users'],
        parameters: [],
        requestBodies: [
            [
                'contentType' => 'application/json',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
                'examples' => [
                    'admin' => [
                        'summary' => 'Admin user',
                        'value' => [
                            'name' => 'Jane Doe',
                        ],
                    ],
                    'player' => [
                        'value' => [
                            'name' => 'Player One',
                        ],
                    ],
                ],
            ],
            [
                'contentType' => 'application/x-www-form-urlencoded',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'example' => 'Form User',
                        ],
                    ],
                ],
                'examples' => [],
            ],
        ],
        responses: [
            '201' => [
                'description' => 'Created',
                'content' => [
                    'application/json' => [
                        'contentType' => 'application/json',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'name' => ['type' => 'string'],
                            ],
                        ],
                        'example' => [
                            'id' => 1,
                            'name' => 'Jane Doe',
                        ],
                        'examples' => [],
                    ],
                ],
            ],
        ],
        security: [],
        deprecated: false,
    );

    $html = view('filament-openapi-docs::components.endpoint', [
        'endpoint' => $endpoint,
    ])->render();

    expect($html)->toContain('Request Sample')
        ->and($html)->toContain('Try it')
        ->and($html)->toContain('Send API request')
        ->and($html)->toContain('Format JSON')
        ->and($html)->toContain('Request sample')
        ->and($html)->toContain('x-data="requestSnippet')
        ->and($html)->toContain('x-load-src')
        ->and($html)->toContain('Response Example')
        ->and($html)->toContain('application/json')
        ->and($html)->toContain('application/x-www-form-urlencoded')
        ->and($html)->toContain('Admin user')
        ->and($html)->toContain('Player')
        ->and($html)->toContain('&quot;name&quot;: &quot;Jane Doe&quot;')
        ->and($html)->toContain('name=Form%20User')
        ->and($html)->toContain('&quot;id&quot;: 1')
        ->and($html)->toContain('foad-sample-select')
        ->and($html)->toContain('x-model="activeSample"')
        ->and($html)->toContain('x-model="bodyText"')
        ->and($html)->toContain('x-on:click="sendRequest()"')
        ->and($html)->toContain('foad-sample-code')
        ->and(file_get_contents(__DIR__.'/../../resources/views/components/endpoint.blade.php'))
        ->toContain('components.request-snippet');
});

it('renders editable try it controls for auth and query request data', function () {
    $endpoint = new Endpoint(
        id: 'get-users',
        method: 'GET',
        path: '/users/{user}',
        summary: 'List users',
        description: null,
        tags: ['Users'],
        parameters: [
            [
                'name' => 'user',
                'in' => 'path',
                'type' => 'integer',
                'required' => true,
                'description' => null,
                'schema' => ['type' => 'integer', 'example' => 5],
                'examples' => [],
            ],
            [
                'name' => 'include',
                'in' => 'query',
                'type' => 'string',
                'required' => false,
                'description' => null,
                'schema' => ['type' => 'string', 'default' => 'profile'],
                'examples' => [],
            ],
            [
                'name' => 'X-Trace',
                'in' => 'header',
                'type' => 'string',
                'required' => false,
                'description' => null,
                'schema' => ['type' => 'string'],
                'example' => 'trace-1',
                'examples' => [],
            ],
        ],
        requestBodies: [],
        responses: [],
        security: [
            ['bearerAuth' => []],
        ],
        deprecated: false,
    );

    $html = view('filament-openapi-docs::components.request-snippet', [
        'endpoint' => $endpoint,
        'servers' => ['https://api.example.test'],
        'components' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                ],
            ],
        ],
    ])->render();

    expect($html)->toContain('Try it')
        ->and($html)->toContain('Send API request')
        ->and($html)->toContain('Developer mode')
        ->and($html)->toContain('fi-fo-toggle')
        ->and($html)->toContain('foad-developer-mode-switch')
        ->and($html)->toContain('foad-developer-mode-knob')
        ->and($html)->toContain('x-model="developerMode"')
        ->and($html)->toContain('Auth')
        ->and($html)->toContain('Headers')
        ->and($html)->toContain('Add header')
        ->and($html)->toContain('Path parameters')
        ->and($html)->toContain('Query parameters')
        ->and($html)->toContain('pathParameters')
        ->and($html)->toContain('headerParameters')
        ->and($html)->toContain('queryParameters')
        ->and($html)->toContain('authParameters')
        ->and($html)->toContain('x-show="developerMode"')
        ->and($html)->toContain('x-on:click="addHeader()"')
        ->and($html)->toContain('x-on:click="removeHeader(index)"')
        ->and($html)->toContain('x-on:click="addQueryParameter()"')
        ->and($html)->toContain('x-on:click="removeQueryParameter(index)"')
        ->and($html)->toContain('x-bind:placeholder="parameter.placeholder"')
        ->and($html)->toContain('x-model="parameter.value"')
        ->and(strpos($html, 'Headers'))->toBeLessThan(strpos($html, 'Path parameters'))
        ->and(strpos($html, 'Path parameters'))->toBeLessThan(strpos($html, 'Query parameters'));
});

it('loads generated default headers into disabled request header controls', function () {
    $script = file_get_contents(__DIR__.'/../../resources/js/request-snippet.js');
    $view = file_get_contents(__DIR__.'/../../resources/views/components/request-snippet.blade.php');

    expect($script)->toContain("disabled: isDefaultHeader(header.name)")
        ->and($script)->toContain("['accept', 'content-type']")
        ->and($script)->toContain('developerMode: false')
        ->and($script)->toContain('addQueryParameter()')
        ->and($script)->toContain('this.developerMode || ! parameter.developerOnly')
        ->and($script)->not->toContain("header.name.toLowerCase() !== 'accept'")
        ->and($script)->not->toContain("header.name.toLowerCase() !== 'content-type'")
        ->and($view)->toContain('x-bind:disabled="parameter.disabled && ! developerMode"');
});

it('inherits global openapi security from scramble authenticated routes', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'info' => [
            'title' => 'Game API',
            'version' => '1.0.0',
        ],
        'security' => [
            ['bearerAuth' => []],
        ],
        'components' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                ],
            ],
        ],
        'paths' => [
            '/profile' => [
                'get' => [
                    'tags' => ['Profile'],
                    'summary' => 'Profile',
                    'responses' => [
                        '200' => ['description' => 'OK'],
                    ],
                ],
            ],
            '/health' => [
                'get' => [
                    'tags' => ['System'],
                    'summary' => 'Health',
                    'security' => [],
                    'responses' => [
                        '200' => ['description' => 'OK'],
                    ],
                ],
            ],
        ],
    ]);

    $protectedEndpoint = $parsed['endpoints']['Profile'][0];
    $publicEndpoint = $parsed['endpoints']['System'][0];
    $presented = app(RequestSnippetPresenter::class)->present($protectedEndpoint, ['https://api.example.test'], $parsed['components']);

    expect($protectedEndpoint->security)->toBe([['bearerAuth' => []]])
        ->and($publicEndpoint->security)->toBe([])
        ->and($presented['requests'][0]['har']['headers'])->toContain([
            'name' => 'Authorization',
            'value' => 'Bearer <token>',
        ]);
});

it('builds accept header from response content for get request samples', function () {
    $endpoint = new Endpoint(
        id: 'get-users',
        method: 'GET',
        path: '/users',
        summary: 'List users',
        description: null,
        tags: ['Users'],
        parameters: [
            [
                'name' => 'Accept',
                'in' => 'header',
                'type' => 'string',
                'required' => false,
                'description' => null,
                'schema' => ['type' => 'string'],
                'example' => 'text/plain',
                'examples' => [],
            ],
        ],
        requestBodies: [],
        responses: [
            '200' => [
                'description' => 'OK',
                'content' => [
                    'application/json' => [
                        'contentType' => 'application/json',
                        'schema' => [
                            'type' => 'object',
                        ],
                        'examples' => [],
                    ],
                ],
            ],
        ],
        security: [],
        deprecated: false,
    );

    $har = app(RequestSnippetPresenter::class)
        ->present($endpoint, ['https://api.example.test'], [])['requests'][0]['har'];

    expect($har['method'])->toBe('GET')
        ->and($har['headers'])->toContain(['name' => 'Accept', 'value' => 'application/json'])
        ->and($har['headers'])->not->toContain(['name' => 'Accept', 'value' => 'text/plain'])
        ->and($har['headers'])->not->toContain(['name' => 'Content-Type', 'value' => 'application/json'])
        ->and($har)->not->toHaveKey('postData');
});

it('builds har request data for httpsnippet samples', function () {
    $endpoint = new Endpoint(
        id: 'post-usersuser',
        method: 'POST',
        path: '/users/{user}',
        summary: 'Update user',
        description: null,
        tags: ['Users'],
        parameters: [
            [
                'name' => 'user',
                'in' => 'path',
                'type' => 'integer',
                'required' => true,
                'description' => null,
                'schema' => ['type' => 'integer', 'example' => 5],
                'examples' => [],
            ],
            [
                'name' => 'include',
                'in' => 'query',
                'type' => 'string',
                'required' => false,
                'description' => null,
                'schema' => ['type' => 'string', 'default' => 'profile'],
                'examples' => [],
            ],
            [
                'name' => 'X-Trace',
                'in' => 'header',
                'type' => 'string',
                'required' => false,
                'description' => null,
                'schema' => ['type' => 'string'],
                'example' => 'trace-1',
                'examples' => [],
            ],
            [
                'name' => 'Accept',
                'in' => 'header',
                'type' => 'string',
                'required' => false,
                'description' => null,
                'schema' => ['type' => 'string'],
                'example' => 'application/json',
                'examples' => [],
            ],
            [
                'name' => 'Authorization',
                'in' => 'header',
                'type' => 'string',
                'required' => false,
                'description' => null,
                'schema' => ['type' => 'string'],
                'example' => 'Token documented-header',
                'examples' => [],
            ],
            [
                'name' => 'Content-Type',
                'in' => 'header',
                'type' => 'string',
                'required' => false,
                'description' => null,
                'schema' => ['type' => 'string'],
                'example' => 'text/plain',
                'examples' => [],
            ],
        ],
        requestBodies: [
            [
                'contentType' => 'application/json',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'example' => 'Jane Doe'],
                    ],
                ],
                'examples' => [],
            ],
        ],
        responses: [],
        security: [
            ['bearerAuth' => []],
            ['apiKey' => []],
        ],
        deprecated: false,
    );

    $presented = app(RequestSnippetPresenter::class)->present($endpoint, ['https://api.example.test'], [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
            ],
            'apiKey' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-Api-Key',
            ],
        ],
    ]);

    $har = $presented['requests'][0]['har'];

    expect($presented['requests'][0]['label'])->toBe('application/json - Generated')
        ->and($har['method'])->toBe('POST')
        ->and($har['url'])->toBe('https://api.example.test/users/5?include=profile')
        ->and($har['queryString'])->toContain(['name' => 'include', 'value' => 'profile'])
        ->and($har['headers'])->toContain(['name' => 'X-Trace', 'value' => 'trace-1'])
        ->and($har['headers'])->toContain(['name' => 'Authorization', 'value' => 'Bearer <token>'])
        ->and($har['headers'])->toContain(['name' => 'X-Api-Key', 'value' => '<api-key>'])
        ->and($har['headers'])->toContain(['name' => 'Content-Type', 'value' => 'application/json'])
        ->and($har['headers'])->not->toContain(['name' => 'Accept', 'value' => 'application/json'])
        ->and($har['headers'])->not->toContain(['name' => 'Authorization', 'value' => 'Token documented-header'])
        ->and($har['headers'])->not->toContain(['name' => 'Content-Type', 'value' => 'text/plain'])
        ->and($har['postData']['mimeType'])->toBe('application/json')
        ->and($har['postData']['text'])->toContain('"name": "Jane Doe"');
});

it('does not render request snippets when disabled', function () {
    config()->set('filament-openapi-docs.request_samples.enabled', false);

    $endpoint = new Endpoint(
        id: 'get-users',
        method: 'GET',
        path: '/users',
        summary: 'List users',
        description: null,
        tags: ['Users'],
        parameters: [],
        requestBodies: [],
        responses: [],
        security: [],
        deprecated: false,
    );

    $html = view('filament-openapi-docs::components.request-snippet', [
        'endpoint' => $endpoint,
        'servers' => ['https://api.example.test'],
        'components' => [],
    ])->render();

    expect($html)->not->toContain('Request sample')
        ->and($html)->not->toContain('requestSnippet');
});

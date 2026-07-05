<?php

use Kramarenko\FilamentOpenApiDocs\DTO\Endpoint;
use Kramarenko\FilamentOpenApiDocs\Services\OpenApiNavigationBuilder;
use Kramarenko\FilamentOpenApiDocs\Services\OpenApiParser;

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
                            'schema' => ['type' => 'string'],
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
        ->and($endpoint->responses['200']['content']['application/json']['type'])->toBe('object');

    expect($parsed['endpoints']['Users'][1]->requestBodies[0]['contentType'])->toBe('application/json');
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
        ->and($html)->toContain('Request data')
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
        ->and($html)->toContain('fi-section')
        ->and($html)->not->toContain('fi-input')
        ->and($html)->not->toContain('fi-collapsible')
        ->and($html)->not->toContain('"properties"')
        ->and($html)->not->toContain('&quot;properties&quot;');
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
        ->and($navigation[0]->getLabel())->toBe('Users')
        ->and($navigation[0]->getItems()[0]->getUrl())->toBe('#')
        ->and($navigation[0]->getItems()[0]->getBadge())->toBe('GET')
        ->and($navigation[0]->getItems()[0]->getBadgeColor())->toBe('success')
        ->and($navigation[0]->getItems()[0]->getExtraAttributes()['wire:click.prevent'])->toBe("selectEndpoint('get-users')");
});

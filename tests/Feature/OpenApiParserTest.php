<?php

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\Enums\HttpMethod;
use Alexkramse\FilamentOpenapiDocs\Services\ExamplePresenter;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiNavigationBuilder;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiParser;
use Alexkramse\FilamentOpenapiDocs\Services\RequestSnippetPresenter;

it('parses openapi paths into grouped endpoints', function () {
    $parsed = app(OpenApiParser::class)->parse(openApiSpec());

    expect($parsed['info']['title'])->toBe('Game API')
        ->and($parsed['servers'])->toBe(['https://example.test/api'])
        ->and($parsed['endpointCount'])->toBe(2)
        ->and($parsed['components']['securitySchemes'])->toHaveKey('bearerAuth')
        ->and($parsed['endpoints'])->toHaveKey('Users');

    $endpoint = $parsed['endpoints']['Users'][0];

    expect($endpoint)->toBeInstanceOf(Endpoint::class)
        ->and($endpoint->method)->toBe('GET')
        ->and($endpoint->path)->toBe('/users/{user}')
        ->and($endpoint->parameters)->toHaveCount(2)
        ->and($endpoint->responses['200']['content']['application/json']['schema']['type'])->toBe('object');
});

it('normalizes swagger 2 request metadata into the same endpoint shape', function () {
    $parsed = app(OpenApiParser::class)->parse(swaggerSpec());
    $endpoint = $parsed['endpoints']['Users'][0];
    $presented = app(RequestSnippetPresenter::class)->present($endpoint, $parsed['servers'], $parsed['components']);

    expect($parsed['servers'])->toBe(['https://api.example.test/v1'])
        ->and($parsed['components']['securitySchemes']['basicAuth']['type'])->toBe('http')
        ->and($endpoint->requestBodies[0]['contentType'])->toBe('application/x-www-form-urlencoded')
        ->and($endpoint->requestBodies[0]['schema']['properties'])->toHaveKeys(['name', 'avatar'])
        ->and($presented['securityItems'][0]['value'])->toBe('Basic <credentials>')
        ->and($presented['mediaHeaders'][0]['name'])->toBe('Content-Type')
        ->and($presented['requests'][0]['har']['headers'])->toContain(['name' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded']);
});

it('provides request docs editable controls and baseline har from one presenter payload', function () {
    $endpoint = endpointWithRequestData();
    $presented = app(RequestSnippetPresenter::class)->present($endpoint, ['https://api.example.test'], securityComponents());
    $request = $presented['requests'][0];

    expect($presented['hasRequestSamples'])->toBeTrue()
        ->and($presented['securityItems'])->toHaveCount(2)
        ->and($presented['mediaHeaders'])->toContain(['name' => 'Content-Type', 'value' => 'application/json', 'description' => 'Request body media type'])
        ->and($presented['headerParameters'][0]['name'])->toBe('X-Trace')
        ->and($presented['pathParameters'][0]['value'])->toBe('5')
        ->and($presented['queryParameters'][0]['value'])->toBe('profile')
        ->and($request['authParameters'])->toHaveCount(2)
        ->and($request['mediaHeaderParameters'][0]['name'])->toBe('Content-Type')
        ->and($request['headerParameters'][0]['name'])->toBe('X-Trace')
        ->and($request['pathParameters'][0]['name'])->toBe('user')
        ->and($request['queryParameters'][0]['name'])->toBe('include')
        ->and($request['har']['url'])->toBe('https://api.example.test/users/5?include=profile')
        ->and($request['har']['headers'])->toContain(['name' => 'Authorization', 'value' => 'Bearer <token>'])
        ->and($request['har']['headers'])->toContain(['name' => 'X-Api-Key', 'value' => '<api-key>'])
        ->and($request['har']['headers'])->toContain(['name' => 'Content-Type', 'value' => 'application/json'])
        ->and($request['har']['headers'])->toContain(['name' => 'X-Trace', 'value' => 'trace-1']);
});

it('detects bearer security from the referenced scheme definition', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.1.0',
        'info' => [
            'title' => 'Laravel',
            'version' => '0.0.1',
        ],
        'servers' => [
            ['url' => 'https://laravel-game-api-dashboard.test/api'],
        ],
        'security' => [
            ['http' => []],
        ],
        'components' => [
            'securitySchemes' => [
                'http' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                ],
            ],
        ],
        'paths' => [
            '/profile' => [
                'get' => [
                    'summary' => 'Profile',
                    'responses' => [
                        '200' => ['description' => 'OK'],
                    ],
                ],
            ],
        ],
    ]);
    $endpoint = $parsed['endpoints']['API'][0];
    $requestData = app(RequestSnippetPresenter::class)->present($endpoint, $parsed['servers'], $parsed['components']);
    $html = html_entity_decode(view('filament-openapi-docs::components.endpoint.request.read', [
        'endpoint' => $endpoint,
        'components' => $parsed['components'],
        'examplePresenter' => app(ExamplePresenter::class),
        'requestData' => $requestData,
    ])->render());

    expect($requestData['securityItems'][0]['label'])->toBe('Bearer token')
        ->and($requestData['securityItems'][0]['schemeType'])->toBe('http')
        ->and($requestData['securityItems'][0]['scheme'])->toBe('bearer')
        ->and($requestData['requests'][0]['har']['headers'])->toContain(['name' => 'Authorization', 'value' => 'Bearer <token>'])
        ->and($html)->toContain('Provide your bearer token in the Authorization header when making requests to protected resources.')
        ->and($html)->toContain('Authorization: Bearer 123')
        ->and($html)->toContain('Header')
        ->and($html)->toContain('Authorization')
        ->and($html)->not->toContain('Auth / Security')
        ->and($html)->not->toContain('Bearer <token>');
});

it('renders non bearer security schemes without bearer documentation', function () {
    $endpoint = new Endpoint(
        id: 'get-security',
        method: 'GET',
        path: '/security',
        summary: 'Security',
        description: null,
        tags: ['Security'],
        parameters: [],
        requestBodies: [],
        responses: [],
        security: [
            ['basicAuth' => []],
            ['apiKeyHeader' => []],
            ['clientCertificate' => []],
            ['oauth' => []],
            ['oidc' => []],
        ],
        deprecated: false,
    );
    $components = [
        'securitySchemes' => [
            'basicAuth' => [
                'type' => 'http',
                'scheme' => 'basic',
            ],
            'apiKeyHeader' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-Api-Key',
            ],
            'clientCertificate' => [
                'type' => 'mutualTLS',
            ],
            'oauth' => [
                'type' => 'oauth2',
                'flows' => [],
            ],
            'oidc' => [
                'type' => 'openIdConnect',
                'openIdConnectUrl' => 'https://example.test/.well-known/openid-configuration',
            ],
        ],
    ];
    $requestData = app(RequestSnippetPresenter::class)->present($endpoint, ['https://api.example.test'], $components);

    expect($requestData['securityItems'])->toHaveCount(5)
        ->and($requestData['securityItems'])->sequence(
            fn ($item) => $item->schemeType->toBe('http')->scheme->toBe('basic')->sendable->toBeTrue(),
            fn ($item) => $item->schemeType->toBe('apiKey')->sendable->toBeTrue(),
            fn ($item) => $item->schemeType->toBe('mutualTLS')->sendable->toBeFalse(),
            fn ($item) => $item->schemeType->toBe('oauth2')->sendable->toBeFalse(),
            fn ($item) => $item->schemeType->toBe('openIdConnect')->sendable->toBeFalse(),
        )
        ->and($requestData['securityItems'])->not->toContain('description', 'Provide your bearer token in the Authorization header when making requests to protected resources.')
        ->and($requestData['requests'][0]['authParameters'])->toHaveCount(2);
});

it('renders request read and send modes', function () {
    $html = html_entity_decode(view('filament-openapi-docs::components.endpoint', [
        'endpoint' => endpointWithRequestData(),
        'servers' => ['https://api.example.test'],
        'components' => securityComponents(),
    ])->render());

    expect($html)->toContain('Send mode')
        ->and($html)->toContain('Security')
        ->and($html)->toContain('Media headers')
        ->and($html)->toContain('Headers')
        ->and($html)->toContain('Path parameters')
        ->and($html)->toContain('Query parameters')
        ->and($html)->toContain('Body')
        ->and($html)->toContain('Tree view')
        ->and($html)->toContain('JSON')
        ->and($html)->toContain('Request sample')
        ->and($html)->toContain('Developer mode')
        ->and($html)->toContain('Send API request')
        ->and($html)->toContain('Add header')
        ->and($html)->toContain('Add')
        ->and(substr_count($html, 'x-data="requestSnippet'))->toBe(1);
});

it('renders request read mode as static documentation rows', function () {
    config()->set('filament-openapi-docs.request_samples.enabled', false);

    $endpoint = endpointWithRequestData();
    $requestData = app(RequestSnippetPresenter::class)->present($endpoint, ['https://api.example.test'], securityComponents());
    $html = html_entity_decode(view('filament-openapi-docs::components.endpoint.request.read', [
        'endpoint' => $endpoint,
        'components' => securityComponents(),
        'examplePresenter' => app(ExamplePresenter::class),
        'requestData' => $requestData,
    ])->render());

    expect($html)->toContain('foad-property-row')
        ->and($html)->toContain('foad-property-name')
        ->and($html)->toContain('Security')
        ->and($html)->toContain('Media headers')
        ->and($html)->toContain('Headers')
        ->and($html)->toContain('Path parameters')
        ->and($html)->toContain('Query parameters')
        ->and($html)->toContain('Required')
        ->and($html)->toContain('Optional')
        ->and($html)->toContain('example: trace-1')
        ->and($html)->toContain('X-Trace')
        ->and($html)->toContain('trace-1')
        ->and($html)->not->toContain('fi-fo-field')
        ->and($html)->not->toContain('fi-input-wrp')
        ->and($html)->not->toContain('readonly');
});

it('does not render request send mode when request samples are disabled', function () {
    config()->set('filament-openapi-docs.request_samples.enabled', false);

    $html = view('filament-openapi-docs::components.endpoint', [
        'endpoint' => endpointWithRequestData(),
        'servers' => ['https://api.example.test'],
        'components' => securityComponents(),
    ])->render();

    expect($html)->toContain('Security')
        ->and($html)->not->toContain('Send API request')
        ->and($html)->not->toContain('requestSnippet(');
});

it('uses shared method color logic for endpoint and navigation badges', function () {
    $endpoint = new Endpoint(
        id: 'delete-users',
        method: 'DELETE',
        path: '/users/{user}',
        summary: 'Delete user',
        description: null,
        tags: ['Users'],
        parameters: [],
        requestBodies: [],
        responses: [],
        security: [],
        deprecated: false,
    );

    $html = view('filament-openapi-docs::components.endpoint', ['endpoint' => $endpoint])->render();
    $navigation = app(OpenApiNavigationBuilder::class)->build(['Users' => [$endpoint]]);

    expect(HttpMethod::color('DELETE'))->toBe('danger')
        ->and($navigation[0]->getItems()[0]->getBadgeColor())->toBe('danger')
        ->and($html)->toContain('DELETE');
});

it('keeps request snippet javascript inside runtime boundaries', function () {
    $script = file_get_contents(__DIR__.'/../../resources/js/request-snippet.js');

    expect($script)->not->toContain('collectAuthParameters')
        ->and($script)->not->toContain('collectHeaderParameters')
        ->and($script)->toContain('applyRuntimeEditsToHar')
        ->and($script)->toContain('new HTTPSnippet')
        ->and($script)->toContain('fetch(')
        ->and($script)->toContain('isValidHeaderName');
});

it('does not contain old package namespace references', function () {
    $paths = collect([
        ...glob(__DIR__.'/../../src/**/*.php'),
        ...glob(__DIR__.'/../../resources/views/**/*.php'),
        ...glob(__DIR__.'/../**/*.php'),
        __DIR__.'/../../composer.json',
    ]);

    $contents = $paths
        ->filter(fn (string $path): bool => is_file($path))
        ->map(fn (string $path): string => file_get_contents($path))
        ->implode("\n");

    $oldVendorNamespace = 'Kram'.'arenko\\'.'FilamentOpenApiDocs';
    $oldVendor = 'Kram'.'arenko';

    expect($contents)->not->toContain($oldVendorNamespace)
        ->and($contents)->not->toContain($oldVendor);
});

function endpointWithRequestData(): Endpoint
{
    return new Endpoint(
        id: 'post-usersuser',
        method: 'POST',
        path: '/users/{user}',
        summary: 'Update user',
        description: null,
        tags: ['Users'],
        parameters: [
            parameter('user', 'path', 'integer', true, ['type' => 'integer', 'example' => 5]),
            parameter('include', 'query', 'string', false, ['type' => 'string', 'default' => 'profile']),
            parameter('X-Trace', 'header', 'string', false, ['type' => 'string'], 'trace-1'),
            parameter('Accept', 'header', 'string', false, ['type' => 'string'], 'text/plain'),
            parameter('Authorization', 'header', 'string', false, ['type' => 'string'], 'Token documented-header'),
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
        responses: [
            '200' => [
                'description' => 'OK',
                'content' => [
                    'application/json' => [
                        'contentType' => 'application/json',
                        'schema' => ['type' => 'object'],
                        'examples' => [],
                    ],
                ],
            ],
        ],
        security: [
            ['bearerAuth' => []],
            ['apiKey' => []],
        ],
        deprecated: false,
    );
}

/**
 * @return array{name: string, in: string, type: string, required: bool, description: ?string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>, default?: mixed}
 */
function parameter(string $name, string $in, string $type, bool $required, array $schema, mixed $example = null): array
{
    return [
        'name' => $name,
        'in' => $in,
        'type' => $type,
        'required' => $required,
        'description' => null,
        'schema' => $schema,
        ...(func_num_args() === 6 ? ['example' => $example] : []),
        'examples' => [],
        ...(array_key_exists('default', $schema) ? ['default' => $schema['default']] : []),
    ];
}

/**
 * @return array<string, mixed>
 */
function securityComponents(): array
{
    return [
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
    ];
}

/**
 * @return array<string, mixed>
 */
function openApiSpec(): array
{
    return [
        'openapi' => '3.1.0',
        'info' => [
            'title' => 'Game API',
            'version' => '1.0.0',
        ],
        'servers' => [
            ['url' => 'https://example.test/api'],
        ],
        'components' => securityComponents(),
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
                            'schema' => ['type' => ['string', 'null'], 'example' => 'profile'],
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
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Created'],
                    ],
                ],
            ],
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function swaggerSpec(): array
{
    return [
        'swagger' => '2.0',
        'info' => [
            'title' => 'Game API',
            'version' => '1.0.0',
        ],
        'host' => 'api.example.test',
        'basePath' => '/v1',
        'schemes' => ['https'],
        'consumes' => ['application/x-www-form-urlencoded'],
        'produces' => ['application/json'],
        'securityDefinitions' => [
            'basicAuth' => [
                'type' => 'basic',
            ],
        ],
        'security' => [
            ['basicAuth' => []],
        ],
        'paths' => [
            '/users' => [
                'post' => [
                    'tags' => ['Users'],
                    'summary' => 'Create user',
                    'parameters' => [
                        [
                            'name' => 'name',
                            'in' => 'formData',
                            'required' => true,
                            'type' => 'string',
                            'description' => 'Display name',
                        ],
                        [
                            'name' => 'avatar',
                            'in' => 'formData',
                            'type' => 'file',
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Created',
                            'schema' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

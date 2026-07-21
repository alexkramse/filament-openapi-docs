<?php

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\Enums\HttpMethod;
use Alexkramse\FilamentOpenapiDocs\Services\ExamplePresenter;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiNavigationBuilder;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiParser;
use Alexkramse\FilamentOpenapiDocs\Services\RequestSnippetPresenter;
use Alexkramse\FilamentOpenapiDocs\Support\HttpStatus;

it('parses openapi paths into grouped endpoints', function () {
    $parsed = app(OpenApiParser::class)->parse(openApiSpec());

    expect($parsed['info']['title'])->toBe('Game API')
        ->and($parsed['servers'])->toBe(['https://example.test/api'])
        ->and($parsed['endpointCount'])->toBe(2)
        ->and($parsed['components']['securitySchemes'])->toHaveKey('bearerAuth')
        ->and($parsed['endpoints'])->toHaveKey('Users');

    $endpoint = $parsed['endpoints']['Users'][0];

    expect($endpoint)->toBeInstanceOf(Endpoint::class)
        ->and($endpoint->id)->toBe('showUser')
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
        ->and($endpoint->id)->toBe('createSwaggerUser')
        ->and($endpoint->requestBodies[0]['contentType'])->toBe('application/x-www-form-urlencoded')
        ->and($endpoint->requestBodies[0]['schema']['properties'])->toHaveKeys(['name', 'avatar'])
        ->and($presented['securityItems'][0]['value'])->toBe('Basic <credentials>')
        ->and($presented['mediaHeaders'][0]['name'])->toBe('Content-Type')
        ->and($presented['requests'][0]['har']['headers'])->toContain(['name' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded']);
});

it('falls back to method and path slug when operation id is missing', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.1.0',
        'info'    => [
            'title'   => 'Laravel',
            'version' => '0.0.1',
        ],
        'paths' => [
            '/health-check' => [
                'get' => [
                    'tags'        => ['System'],
                    'operationId' => '   ',
                    'responses'   => [
                        '200' => ['description' => 'OK'],
                    ],
                ],
            ],
        ],
    ]);

    expect($parsed['endpoints']['System'][0]->id)->toBe('get-health-check');
});

it('preserves endpoint group order from the openapi paths', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.1.0',
        'info'    => [
            'title'   => 'Laravel',
            'version' => '0.0.1',
        ],
        'paths' => [
            '/feedback' => [
                'post' => [
                    'tags'      => ['Feedback'],
                    'responses' => [
                        '202' => ['description' => 'Accepted'],
                    ],
                ],
            ],
            '/accounts' => [
                'get' => [
                    'tags'      => ['Accounts'],
                    'responses' => [
                        '200' => ['description' => 'OK'],
                    ],
                ],
            ],
            '/games' => [
                'get' => [
                    'tags'      => ['Games'],
                    'responses' => [
                        '200' => ['description' => 'OK'],
                    ],
                ],
            ],
        ],
    ]);

    expect(array_keys($parsed['endpoints']))->toBe(['Feedback', 'Accounts', 'Games']);
});

it('uses documented content type header as a request sample media type override', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.0.0',
        'info'    => [
            'title'   => 'Game API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/v1/feedback' => [
                'post' => [
                    'tags'        => ['Demo API'],
                    'operationId' => 'demoFeedbackSubmit',
                    'parameters'  => [
                        [
                            'name'        => 'Content-Type',
                            'in'          => 'header',
                            'required'    => true,
                            'description' => 'Use application/x-www-form-urlencoded for this demo.',
                            'schema'      => ['type' => 'string'],
                            'example'     => 'application/x-www-form-urlencoded',
                        ],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type'    => 'string',
                                            'example' => 'Katherine Johnson',
                                        ],
                                        'email' => [
                                            'type'    => 'string',
                                            'format'  => 'email',
                                            'example' => 'katherine@example.test',
                                        ],
                                        'rating' => [
                                            'type'    => 'integer',
                                            'example' => 5,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '202' => ['description' => 'Accepted'],
                    ],
                ],
            ],
        ],
    ]);
    $endpoint = $parsed['endpoints']['Demo API'][0];
    $presented = app(RequestSnippetPresenter::class)->present($endpoint, [], $parsed['components']);
    $request = $presented['requests'][0];
    $html = renderParserOpenApiDocsEndpoint($endpoint, [], $parsed['components']);

    expect($endpoint->requestBodies[0]['contentType'])->toBe('application/json')
        ->and($presented['headerParameters'])->toBe([])
        ->and($html)->toContain('Form request')
        ->and($html)->toContain('Body')
        ->and($html)->toContain('application/x-www-form-urlencoded')
        ->and($html)->toContain('foad-request-body-sample-wrap')
        ->and($html)->toContain('name=Katherine%20Johnson\\u0026email=katherine%40example.test\\u0026rating=5')
        ->and($html)->not->toContain('URL encoded')
        ->and($html)->not->toContain('Tree view')
        ->and($html)->not->toContain('Type: application/json')
        ->and($presented['mediaHeaders'])->toContain([
            'name'        => 'Content-Type',
            'value'       => 'application/x-www-form-urlencoded',
            'description' => 'Request body media type',
        ])
        ->and($request['label'])->toBe('application/x-www-form-urlencoded - Generated')
        ->and($presented['requestBodies'][0]['contentType'])->toBe('application/x-www-form-urlencoded')
        ->and($request['bodyMimeType'])->toBe('application/x-www-form-urlencoded')
        ->and($request['formParameters'])->toBe([
            [
                'name'          => 'name',
                'value'         => 'Katherine Johnson',
                'developerOnly' => false,
                'removable'     => false,
            ],
            [
                'name'          => 'email',
                'value'         => 'katherine@example.test',
                'developerOnly' => false,
                'removable'     => false,
            ],
            [
                'name'          => 'rating',
                'value'         => '5',
                'developerOnly' => false,
                'removable'     => false,
            ],
        ])
        ->and($request['bodyText'])->toBe('name=Katherine%20Johnson&email=katherine%40example.test&rating=5')
        ->and($request['har']['headers'])->toContain(['name' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'])
        ->and($request['har']['postData'])->toBe([
            'mimeType' => 'application/x-www-form-urlencoded',
            'text'     => 'name=Katherine%20Johnson&email=katherine%40example.test&rating=5',
            'params'   => [
                [
                    'name'  => 'name',
                    'value' => 'Katherine Johnson',
                ],
                [
                    'name'  => 'email',
                    'value' => 'katherine@example.test',
                ],
                [
                    'name'  => 'rating',
                    'value' => '5',
                ],
            ],
        ]);
});

it('reuses multipart form data schema fields as send mode form inputs with file uploads', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.1.0',
        'info'    => [
            'title'   => 'Game API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/v1/assets' => [
                'post' => [
                    'tags'        => ['Assets'],
                    'operationId' => 'uploadAsset',
                    'requestBody' => [
                        'content' => [
                            'multipart/form-data' => [
                                'schema' => [
                                    'allOf' => [
                                        [
                                            '$ref' => '#/components/schemas/AssetUploadRequest',
                                        ],
                                        [
                                            'type'       => 'object',
                                            'required'   => ['asset'],
                                            'properties' => [
                                                'asset' => [
                                                    'type'             => 'string',
                                                    'format'           => 'binary',
                                                    'contentMediaType' => 'video/mp4',
                                                ],
                                                'screenshots' => [
                                                    'type'  => 'array',
                                                    'items' => [
                                                        'type'             => 'string',
                                                        'format'           => 'binary',
                                                        'contentMediaType' => 'image/png',
                                                    ],
                                                ],
                                                'archive' => [
                                                    'type'             => 'string',
                                                    'contentEncoding'  => 'base64',
                                                    'contentMediaType' => 'application/zip',
                                                ],
                                            ],
                                        ],
                                        [
                                            'oneOf' => [
                                                [
                                                    'type'       => 'object',
                                                    'properties' => [
                                                        'notes' => [
                                                            'type'    => 'string',
                                                            'example' => 'Internal only',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'anyOf' => [
                                                [
                                                    'type'       => 'object',
                                                    'properties' => [
                                                        'thumbnail' => [
                                                            'type'             => 'string',
                                                            'format'           => 'base64',
                                                            'contentMediaType' => 'image/jpeg',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'type'       => 'object',
                                            'properties' => [
                                                'legacy_upload' => [
                                                    'type'             => 'string',
                                                    'contentEncoding'  => 'base64',
                                                    'contentMediaType' => 'application/pdf',
                                                ],
                                            ],
                                        ],
                                        [
                                            'type'       => 'object',
                                            'properties' => [
                                                'legacy_file' => [
                                                    'type' => 'file',
                                                ],
                                            ],
                                        ],
                                        [
                                            'type'       => 'object',
                                            'properties' => [
                                                'binary_value' => [
                                                    'type'   => 'string',
                                                    'format' => 'base64',
                                                ],
                                            ],
                                        ],
                                        [
                                            'type'       => 'object',
                                            'properties' => [
                                                'other_file' => [
                                                    '$ref' => '#/components/schemas/PdfFile',
                                                ],
                                            ],
                                        ],
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
        'components' => [
            'schemas' => [
                'AssetUploadRequest' => [
                    'type'       => 'object',
                    'required'   => ['title'],
                    'properties' => [
                        'title' => [
                            'type'    => 'string',
                            'example' => 'Launch trailer',
                        ],
                    ],
                ],
                'PdfFile' => [
                    'type'             => 'string',
                    'format'           => 'binary',
                    'contentMediaType' => 'application/pdf',
                ],
            ],
        ],
    ]);
    $endpoint = $parsed['endpoints']['Assets'][0];
    $presented = app(RequestSnippetPresenter::class)->present($endpoint, ['https://api.example.test'], $parsed['components']);
    $request = $presented['requests'][0];
    $html = html_entity_decode(renderParserOpenApiDocsEndpoint($endpoint, ['https://api.example.test'], $parsed['components']));
    $formRequestMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/request/tester/form-request.blade.php');
    $requestSnippetRuntime = file_get_contents(__DIR__.'/../../resources/js/request-snippet.js');
    $distRuntime = file_get_contents(__DIR__.'/../../resources/js/dist/request-snippet.js');

    expect($endpoint->requestBodies[0]['contentType'])->toBe('multipart/form-data')
        ->and($presented['mediaHeaders'])->toContain([
            'name'        => 'Content-Type',
            'value'       => 'multipart/form-data',
            'description' => 'Request body media type',
        ])
        ->and(collect($request['formParameters'])->pluck('name')->all())->toBe([
            'title',
            'asset',
            'screenshots',
            'archive',
            'notes',
            'thumbnail',
            'legacy_upload',
            'legacy_file',
            'binary_value',
            'other_file',
        ])
        ->and(collect($request['formParameters'])->where('type', 'file')->pluck('name')->values()->all())->toBe([
            'asset',
            'screenshots',
            'archive',
            'thumbnail',
            'legacy_upload',
            'legacy_file',
            'binary_value',
            'other_file',
        ])
        ->and($request['formParameters'][0])->toBe([
            'name'          => 'title',
            'value'         => 'Launch trailer',
            'type'          => 'text',
            'multiple'      => false,
            'contentType'   => null,
            'required'      => true,
            'developerOnly' => false,
            'removable'     => false,
        ])
        ->and($request['formParameters'][1])->toBe([
            'name'          => 'asset',
            'value'         => '',
            'type'          => 'file',
            'multiple'      => false,
            'contentType'   => 'video/mp4',
            'required'      => true,
            'developerOnly' => false,
            'removable'     => false,
        ])
        ->and($request['formParameters'][2])->toBe([
            'name'          => 'screenshots',
            'value'         => '',
            'type'          => 'file',
            'multiple'      => true,
            'contentType'   => 'image/png',
            'required'      => false,
            'developerOnly' => false,
            'removable'     => false,
        ])
        ->and($request['har']['postData']['mimeType'])->toBe('multipart/form-data')
        ->and($request['har']['postData']['text'])->toBe('')
        ->and($request['har']['postData']['params'])->toContain([
            'name'  => 'title',
            'value' => 'Launch trailer',
        ])
        ->and($request['har']['postData']['params'])->toContain([
            'name'        => 'other_file',
            'value'       => '',
            'fileName'    => 'other_file',
            'contentType' => 'application/pdf',
        ])
        ->and($html)->toContain('Form request')
        ->and($html)->toContain('multipart/form-data')
        ->and($html)->toContain('type="file"')
        ->and($html)->toContain('x-on:change="setFormParameterFiles(index, $event.target.files)"')
        ->and($formRequestMarkup)->toContain('hasFormRequestBody')
        ->and($formRequestMarkup)->toContain('parameter.type === \'file\'')
        ->and($requestSnippetRuntime)->toContain('hasMultipartFormDataBody')
        ->and($requestSnippetRuntime)->toContain('const requestBody = isMultipartFormData')
        ->and($requestSnippetRuntime)->toContain('const responseBody = await response.text()')
        ->and($requestSnippetRuntime)->toContain('new FormData()')
        ->and($requestSnippetRuntime)->toContain('header.name.toLowerCase() === "content-type"')
        ->and($requestSnippetRuntime)->toContain('formData.append(parameter.name, file)')
        ->and($distRuntime)->not->toContain('body:["GET","HEAD"].includes(o)?void 0:c');
});

it('resolves referenced response objects into documented response content', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.0.0',
        'info'    => [
            'title'   => 'Game API',
            'version' => '1.0.0',
        ],
        'components' => [
            'responses' => [
                'ValidationException' => [
                    'description' => 'Validation error',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ValidationException',
                            ],
                        ],
                    ],
                ],
            ],
            'schemas' => [
                'ValidationException' => [
                    'type'       => 'object',
                    'properties' => [
                        'message' => ['type' => 'string'],
                        'errors'  => ['type' => 'object'],
                    ],
                ],
            ],
        ],
        'paths' => [
            '/v1/feedback' => [
                'post' => [
                    'tags'        => ['Demo API'],
                    'operationId' => 'demoFeedbackSubmit',
                    'responses'   => [
                        '422' => [
                            '$ref' => '#/components/responses/ValidationException',
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $response = $parsed['endpoints']['Demo API'][0]->responses['422'];

    expect($response['description'])->toBe('Validation error')
        ->and($response['content'])->toHaveKey('application/json')
        ->and($response['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/ValidationException');
});

it('provides request docs editable controls and baseline har from one presenter payload', function () {
    $endpoint = endpointWithRequestData();
    $presented = app(RequestSnippetPresenter::class)->present($endpoint, ['https://api.example.test'], securityComponents());
    $request = $presented['requests'][0];

    expect($presented['hasRequestSamples'])->toBeTrue()
        ->and($presented['securityItems'])->toHaveCount(2)
        ->and($presented['mediaHeaders'])->toContain(['name' => 'Content-Type', 'value' => 'application/json', 'description' => 'Request body media type'])
        ->and($presented['headerParameters'][0]['name'])->toBe('X-Trace')
        ->and($presented['cookieParameters'][0]['name'])->toBe('demo_session')
        ->and($presented['pathParameters'][0]['value'])->toBe('5')
        ->and($presented['queryParameters'][0]['value'])->toBe('profile')
        ->and($request['authParameters'])->toHaveCount(2)
        ->and($request['mediaHeaderParameters'][0]['name'])->toBe('Content-Type')
        ->and($request['headerParameters'][0]['name'])->toBe('X-Trace')
        ->and($request['cookieParameters'][0]['name'])->toBe('demo_session')
        ->and($request['pathParameters'][0]['name'])->toBe('user')
        ->and($request['queryParameters'][0]['name'])->toBe('include')
        ->and($request['formParameters'])->toBe([])
        ->and($request['har']['url'])->toBe('https://api.example.test/users/5?include=profile')
        ->and($request['har']['cookies'])->toContain(['name' => 'demo_session', 'value' => 'session-token'])
        ->and($request['har']['headers'])->toContain(['name' => 'Authorization', 'value' => 'Bearer <token>'])
        ->and($request['har']['headers'])->toContain(['name' => 'X-Api-Key', 'value' => '<api-key>'])
        ->and($request['har']['headers'])->toContain(['name' => 'Content-Type', 'value' => 'application/json'])
        ->and($request['har']['headers'])->toContain(['name' => 'X-Trace', 'value' => 'trace-1']);
});

it('detects bearer security from the referenced scheme definition', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.1.0',
        'info'    => [
            'title'   => 'Laravel',
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
                    'type'   => 'http',
                    'scheme' => 'bearer',
                ],
            ],
        ],
        'paths' => [
            '/profile' => [
                'get' => [
                    'summary'   => 'Profile',
                    'responses' => [
                        '200' => ['description' => 'OK'],
                    ],
                ],
            ],
        ],
    ]);
    $endpoint = $parsed['endpoints']['API'][0];
    $requestData = app(RequestSnippetPresenter::class)->present($endpoint, $parsed['servers'], $parsed['components']);
    $html = html_entity_decode(view('filament-openapi-docs::openapi-docs.request.data', [
        'endpoint'         => $endpoint,
        'components'       => $parsed['components'],
        'examplePresenter' => app(ExamplePresenter::class),
        'requestData'      => $requestData,
    ])->render());

    expect($requestData['securityItems'][0]['label'])->toBe('Bearer token')
        ->and($requestData['securityItems'][0]['schemeType'])->toBe('http')
        ->and($requestData['securityItems'][0]['scheme'])->toBe('bearer')
        ->and($requestData['requests'][0]['har']['headers'])->toContain(['name' => 'Authorization', 'value' => 'Bearer <token>'])
        ->and($html)->toContain('Provide your bearer token in the Authorization header when making requests to protected resources.')
        ->and($html)->toContain('Authorization: Bearer <token>')
        ->and($html)->toContain('Bearer 123')
        ->and($html)->toContain('Authorization')
        ->and($html)->not->toContain('Auth / Security');
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
            ['apiKeyHeader'      => []],
            ['clientCertificate' => []],
            ['oauth'             => []],
            ['oidc'              => []],
        ],
        deprecated: false,
    );
    $components = [
        'securitySchemes' => [
            'basicAuth' => [
                'type'   => 'http',
                'scheme' => 'basic',
            ],
            'apiKeyHeader' => [
                'type' => 'apiKey',
                'in'   => 'header',
                'name' => 'X-Api-Key',
            ],
            'clientCertificate' => [
                'type' => 'mutualTLS',
            ],
            'oauth' => [
                'type'  => 'oauth2',
                'flows' => [],
            ],
            'oidc' => [
                'type'             => 'openIdConnect',
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
    $html = html_entity_decode(renderParserOpenApiDocsEndpoint(
        endpointWithRequestData(),
        ['https://api.example.test'],
        securityComponents(),
    ));

    $removableParameterMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/request/tester/headers.blade.php')
        .file_get_contents(__DIR__.'/../../resources/views/openapi-docs/request/tester/query-parameters.blade.php');
    $requestSnippetMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/http-snippet.blade.php');
    $pageMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs.blade.php');
    $infoMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/info.blade.php');
    $requestMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/request.blade.php');
    $readModeMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/request/data.blade.php');
    $sendModeMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/request/tester.blade.php');
    $formRequestMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/request/tester/form-request.blade.php');
    $bodyMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/request/tester/body.blade.php');
    $responsePreviewMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/request/tester/response-preview.blade.php');
    $requestSnippetRuntime = file_get_contents(__DIR__.'/../../resources/js/request-snippet.js');

    expect($html)->toContain('Send mode')
        ->and($html)->toContain('Security')
        ->and($html)->not->toContain('Media headers')
        ->and($html)->toContain('Headers')
        ->and($html)->toContain('Content-Type: application/json')
        ->and($html)->toContain('Cookies')
        ->and($html)->toContain('Path parameters')
        ->and($html)->toContain('Query parameters')
        ->and(strpos($html, 'Headers') < strpos($html, 'Cookies'))->toBeTrue()
        ->and(strpos($html, 'Cookies') < strpos($html, 'Path parameters'))->toBeTrue()
        ->and($html)->toContain('Body')
        ->and($html)->toContain('Request sample')
        ->and($html)->toContain('Developer mode')
        ->and($html)->toContain("x-text=\"sending ? 'Sending' : 'Send API request'\"")
        ->and($html)->toContain('x-on:click="copy()"')
        ->and($html)->not->toContain('@js(')
        ->and($html)->toContain('Add header')
        ->and($html)->toContain('Add')
        ->and($html)->toContain('foad-inline-list foad-inline-list-end foad-inline-list-md')
        ->and($html)->toContain('foad-request-mode-controls')
        ->and($html)->toContain('copyToClipboard')
        ->and($html)->toContain('foad-copyable-badge')
        ->and($html)->toContain('Click to copy: \\/users\\/{user}')
        ->and($html)->toContain('fi-grid foad-send-layout md:fi-grid-cols')
        ->and($html)->toContain('--cols-default: repeat(1, minmax(0, 1fr));')
        ->and($html)->toContain('--cols-md: repeat(2, minmax(0, 1fr));')
        ->and($html)->toContain('highlightedResponseBody')
        ->and($html)->not->toContain('textcommit')
        ->and($html)->toContain('await navigator.clipboard.writeText(text)')
        ->and($html)->toContain('https:\\/\\/api.example.test\\/users')
        ->and($html)->toContain('demo_session')
        ->and(substr_count($html, 'foad-send-controls-grid'))->toBe(6)
        ->and(substr_count($html, 'foad-send-controls foad-justify-content-space-between'))->toBe(3)
        ->and(substr_count($html, 'class="foad-header-row"'))->toBe(3)
        ->and($pageMarkup)->toContain('x-data="requestSnippet(@js($requestData))"')
        ->and(substr_count($removableParameterMarkup, '<x-filament::icon-button'))->toBe(2)
        ->and(substr_count($removableParameterMarkup, 'icon="heroicon-m-x-mark"'))->toBe(2)
        ->and(substr_count($removableParameterMarkup, "label=\"{{ __('filament-openapi-docs::ui.actions.remove') }}\""))->toBe(2)
        ->and($removableParameterMarkup)->not->toContain('>Remove')
        ->and($removableParameterMarkup)->toContain('x-for="parameter in mediaHeaderParameters"')
        ->and($removableParameterMarkup)->toContain('x-bind:key="`media-header-${parameter.name}`"')
        ->and(substr_count($removableParameterMarkup, 'x-bind:disabled="!canUseDeveloperOptions"'))->toBe(2)
        ->and($removableParameterMarkup)->toContain('x-bind:id="`header-name-${index}`"')
        ->and($removableParameterMarkup)->toContain('x-bind:id="`parameter-name-${index}`"')
        ->and($removableParameterMarkup)->toContain('x-bind:id="`header-value-${index}`"')
        ->and($removableParameterMarkup)->toContain('x-bind:id="`parameter-value-${index}`"')
        ->and($requestSnippetMarkup)->toContain('icon="heroicon-m-document-duplicate"')
        ->and($requestMarkup)->toContain('x-model="sendMode"')
        ->and($requestMarkup)->toContain('x-show="sendMode && hasDeveloperOptions"')
        ->and($requestMarkup)->toContain('x-model="developerMode"')
        ->and($infoMarkup)->toContain('<x-filament-openapi-docs::copyable-badge')
        ->and($pageMarkup)->not->toContain('async copy(server)')
        ->and($pageMarkup)->not->toContain('<div'.PHP_EOL.'                @if ($requestData[\'hasRequestSamples\'])')
        ->and($readModeMarkup)->toContain('class="fi-grid foad-send-layout md:fi-grid-cols"')
        ->and($readModeMarkup)->toContain('--cols-default: repeat(1, minmax(0, 1fr));')
        ->and($readModeMarkup)->toContain('--cols-md: repeat(2, minmax(0, 1fr));')
        ->and($readModeMarkup)->toContain('ui.labels.cookies')
        ->and($requestSnippetRuntime)->toContain('this.queryParameters.length > 0')
        ->and($requestSnippetRuntime)->toContain('this.cookieParameters.length > 0')
        ->and($requestSnippetRuntime)->toContain('har.cookies = this.cookieParameters')
        ->and($requestSnippetRuntime)->toContain('this.mediaHeaderParameters.length > 0')
        ->and($requestSnippetRuntime)->toContain('hasFormUrlEncodedBody')
        ->and($requestSnippetRuntime)->toContain('encodeFormParameters')
        ->and($sendModeMarkup)->toContain('request.tester.form-request')
        ->and($formRequestMarkup)->toContain('ui.labels.form_request')
        ->and($formRequestMarkup)->toContain('x-for="(parameter, index) in formParameters"')
        ->and($bodyMarkup)->not->toContain('ui.labels.form_request')
        ->and($bodyMarkup)->not->toContain('x-for="(parameter, index) in formParameters"')
        ->and($bodyMarkup)->toContain('x-bind:value="encodedFormBodyText()"')
        ->and($requestSnippetRuntime)->toContain('hasDeveloperOptions: Boolean(config.hasDeveloperOptions ?? false)')
        ->and($requestSnippetRuntime)->toContain('this.hasDeveloperOptions && this.developerMode')
        ->and($sendModeMarkup)->not->toContain('media-headers')
        ->and($responsePreviewMarkup)->not->toContain('media-headers')
        ->and($sendModeMarkup)->not->toContain('TODO')
        ->and($sendModeMarkup)->not->toContain('Developer mode');
});

it('renders request read mode as static documentation rows', function () {
    config()->set('filament-openapi-docs.request_samples.enabled', false);

    $endpoint = endpointWithRequestData();
    $requestData = app(RequestSnippetPresenter::class)->present($endpoint, ['https://api.example.test'], securityComponents());
    $html = html_entity_decode(view('filament-openapi-docs::openapi-docs.request.data', [
        'endpoint'         => $endpoint,
        'components'       => securityComponents(),
        'examplePresenter' => app(ExamplePresenter::class),
        'requestData'      => $requestData,
    ])->render());

    expect($html)->toContain('foad-property-row')
        ->and($html)->toContain('foad-property-name')
        ->and($html)->toContain('Security')
        ->and($html)->not->toContain('Media headers')
        ->and($html)->toContain('Headers')
        ->and($html)->toContain('Content-Type: application/json')
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

    $html = renderParserOpenApiDocsEndpoint(
        endpointWithRequestData(),
        ['https://api.example.test'],
        securityComponents(),
    );
    $pageMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs.blade.php');

    expect($html)->toContain('Security')
        ->and($html)->toContain('Request sample')
        ->and($html)->toContain('No request sample available.')
        ->and($html)->not->toContain('Send API request')
        ->and($pageMarkup)->toContain('requestSnippet(');
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

    $html = view('filament-openapi-docs::openapi-docs.info', [
        'endpoint'          => $endpoint,
        'documentedServers' => [],
    ])->render();
    $navigation = app(OpenApiNavigationBuilder::class)->build(['Users' => [$endpoint]]);

    expect(HttpMethod::color('DELETE'))->toBe('danger')
        ->and($navigation[0]->getItems()[0]->getBadgeColor())->toBe('danger')
        ->and($html)->toContain('DELETE');
});

it('uses shared response status color logic for response badges', function () {
    $responseMarkup = file_get_contents(__DIR__.'/../../resources/views/openapi-docs/response/item.blade.php');

    expect(HttpStatus::color('200'))->toBe('success')
        ->and(HttpStatus::color(201))->toBe('success')
        ->and(HttpStatus::color('302'))->toBe('warning')
        ->and(HttpStatus::color('404'))->toBe('warning')
        ->and(HttpStatus::color('500'))->toBe('danger')
        ->and(HttpStatus::color('100'))->toBe('gray')
        ->and(HttpStatus::color('default'))->toBe('gray')
        ->and($responseMarkup)->toContain('HttpStatus::color($status)')
        ->and($responseMarkup)->not->toContain('str_starts_with((string) $status');
});

it('keeps request snippet javascript inside runtime boundaries', function () {
    $script = file_get_contents(__DIR__.'/../../resources/js/request-snippet.js');

    expect($script)->not->toContain('collectAuthParameters')
        ->and($script)->not->toContain('collectHeaderParameters')
        ->and($script)->toContain('const defaultTargetKey = "shell"')
        ->and($script)->toContain('const defaultClientKey = "curl"')
        ->and($script)->toContain('defaultClientForSelectedTarget')
        ->and($script)->toContain('applyRuntimeEditsToHar')
        ->and($script)->toContain('responseStatusClass(status)')
        ->and($script)->toContain('value.startsWith("2")')
        ->and($script)->toContain('value.startsWith("3") || value.startsWith("4")')
        ->and($script)->toContain('value.startsWith("5")')
        ->and($script)->toContain('responseStatusLabel(status)')
        ->and($script)->toContain('this.message("responseStatusBadge", "Status: :status"')
        ->and($script)->toContain('responseTypeLabel(type)')
        ->and($script)->toContain('this.message("responseTypeBadge", "Type: :type"')
        ->and($script)->toContain('new HTTPSnippet')
        ->and($script)->toContain('navigator.clipboard?.writeText')
        ->and($script)->toContain('window.clearTimeout(this.copyTimeout)')
        ->and($script)->toContain('new FilamentNotification()')
        ->and($script)->toContain('.title(this.message("copiedToClipboard", "Copied to clipboard."))')
        ->and($script)->toContain('.title(this.message("copyFailed", "Copy failed."))')
        ->and($script)->toContain('highlightedBodyText')
        ->and($script)->toContain('Prism.languages.json')
        ->and($script)->toContain('Prism.highlight(visibleCode, grammar, "json")')
        ->and($script)->toContain('escapeHtml(visibleCode)')
        ->and($script)->toContain('samplePrismLanguage(contentType)')
        ->and($script)->toContain('normalizedContentType.includes("json")')
        ->and($script)->toContain('highlightSample(value, contentType)')
        ->and($script)->toContain('Prism.highlight(code, grammar, language)')
        ->and($script)->toContain('responsePrismLanguage')
        ->and($script)->toContain('contentType.includes("json")')
        ->and($script)->toContain('contentType.includes("xml")')
        ->and($script)->toContain('highlightedResponseBody')
        ->and($script)->toContain('Prism.highlight(body, grammar, this.responsePrismLanguage)')
        ->and($script)->toContain('syncBodyEditorScroll(event)')
        ->and($script)->toContain('this.$refs.bodyHighlightScroller')
        ->and($script)->toContain('this.formatJsonBody(false)')
        ->and($script)->toContain('formatJsonBody(showErrors = true, value = this.bodyText)')
        ->and($script)->toContain('async copyBody()')
        ->and($script)->toContain('navigator.clipboard.writeText(this.bodyText)')
        ->and($script)->toContain('async copyResponseBody()')
        ->and($script)->toContain('navigator.clipboard.writeText(this.response.body)')
        ->and($script)->toContain('.success()')
        ->and($script)->toContain('.danger()')
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

it('parses response headers into structured header entries', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.1.0',
        'info'    => [
            'title'   => 'Game API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/stream' => [
                'get' => [
                    'tags'        => ['Streaming'],
                    'operationId' => 'streamEvents',
                    'responses'   => [
                        '200' => [
                            'description' => 'Server-sent event stream.',
                            'content'     => [
                                'text/event-stream' => [
                                    'schema' => [
                                        'type'     => 'string',
                                        'examples' => ["event: demo\ndata: ready\n\n"],
                                    ],
                                ],
                            ],
                            'headers' => [
                                'Transfer-Encoding' => [
                                    'required' => true,
                                    'schema'   => [
                                        'type' => 'string',
                                        'enum' => ['chunked'],
                                    ],
                                ],
                                'X-RateLimit-Limit' => [
                                    'required'    => true,
                                    'schema'      => ['type' => 'integer'],
                                    'description' => 'Rate limit per hour.',
                                    'example'     => 1000,
                                ],
                                'X-Deprecated' => [
                                    'deprecated' => true,
                                    'schema'     => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $endpoint = $parsed['endpoints']['Streaming'][0];
    $headers = $endpoint->responses['200']['headers'];

    expect($headers)->toHaveCount(3)
        ->and($headers[0])->toBe([
            'name'        => 'Transfer-Encoding',
            'type'        => 'string',
            'required'    => true,
            'description' => null,
            'schema'      => ['type' => 'string', 'enum' => ['chunked']],
            'examples'    => [],
            'deprecated'  => false,
        ])
        ->and($headers[1])->toBe([
            'name'        => 'X-RateLimit-Limit',
            'type'        => 'integer',
            'required'    => true,
            'description' => 'Rate limit per hour.',
            'schema'      => ['type' => 'integer'],
            'example'     => 1000,
            'examples'    => [],
            'deprecated'  => false,
        ])
        ->and($headers[2])->toBe([
            'name'        => 'X-Deprecated',
            'type'        => 'string',
            'required'    => false,
            'description' => null,
            'schema'      => ['type' => 'string'],
            'examples'    => [],
            'deprecated'  => true,
        ]);
});

it('renders response headers in the documentation output', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.1.0',
        'info'    => [
            'title'   => 'Game API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/stream' => [
                'get' => [
                    'tags'        => ['Streaming'],
                    'operationId' => 'streamEvents',
                    'responses'   => [
                        '200' => [
                            'description' => 'Server-sent event stream.',
                            'content'     => [
                                'text/event-stream' => [
                                    'schema' => ['type' => 'string'],
                                ],
                            ],
                            'headers' => [
                                'Transfer-Encoding' => [
                                    'required' => true,
                                    'schema'   => [
                                        'type' => 'string',
                                        'enum' => ['chunked'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $endpoint = $parsed['endpoints']['Streaming'][0];
    $html = renderParserOpenApiDocsEndpoint($endpoint, [], $parsed['components']);

    expect($html)->toContain('Headers')
        ->and($html)->toContain('Transfer-Encoding')
        ->and($html)->toContain('string')
        ->and($html)->toContain('Required');
});

it('resolves referenced response headers from components', function () {
    $parsed = app(OpenApiParser::class)->parse([
        'openapi' => '3.1.0',
        'info'    => [
            'title'   => 'Game API',
            'version' => '1.0.0',
        ],
        'components' => [
            'headers' => [
                'XRateLimit' => [
                    'description' => 'Rate limit per hour.',
                    'required'    => true,
                    'schema'      => ['type' => 'integer'],
                    'example'     => 100,
                ],
            ],
        ],
        'paths' => [
            '/rate-limited' => [
                'get' => [
                    'tags'        => ['RateLimiting'],
                    'operationId' => 'getResource',
                    'responses'   => [
                        '200' => [
                            'description' => 'OK',
                            'content'     => [
                                'application/json' => [
                                    'schema' => ['type' => 'object'],
                                ],
                            ],
                            'headers' => [
                                'X-Rate-Limit' => [
                                    '$ref' => '#/components/headers/XRateLimit',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $endpoint = $parsed['endpoints']['RateLimiting'][0];
    $headers = $endpoint->responses['200']['headers'];

    expect($headers)->toHaveCount(1)
        ->and($headers[0])->toBe([
            'name'        => 'X-Rate-Limit',
            'type'        => 'integer',
            'required'    => true,
            'description' => 'Rate limit per hour.',
            'schema'      => ['type' => 'integer'],
            'example'     => 100,
            'examples'    => [],
            'deprecated'  => false,
        ]);
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
            parameter('demo_session', 'cookie', 'string', false, ['type' => 'string'], 'session-token'),
            parameter('Accept', 'header', 'string', false, ['type' => 'string'], 'text/plain'),
            parameter('Authorization', 'header', 'string', false, ['type' => 'string'], 'Token documented-header'),
        ],
        requestBodies: [
            [
                'contentType' => 'application/json',
                'schema'      => [
                    'type'       => 'object',
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
                'content'     => [
                    'application/json' => [
                        'contentType' => 'application/json',
                        'schema'      => ['type' => 'object'],
                        'examples'    => [],
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

function renderParserOpenApiDocsEndpoint(Endpoint $endpoint, array $servers = [], array $components = []): string
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

/**
 * @return array{name: string, in: string, type: string, required: bool, description: ?string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>, default?: mixed}
 */
function parameter(string $name, string $in, string $type, bool $required, array $schema, mixed $example = null): array
{
    return [
        'name'        => $name,
        'in'          => $in,
        'type'        => $type,
        'required'    => $required,
        'description' => null,
        'schema'      => $schema,
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
                'type'   => 'http',
                'scheme' => 'bearer',
            ],
            'apiKey' => [
                'type' => 'apiKey',
                'in'   => 'header',
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
        'info'    => [
            'title'   => 'Game API',
            'version' => '1.0.0',
        ],
        'servers' => [
            ['url' => 'https://example.test/api'],
        ],
        'components' => securityComponents(),
        'paths'      => [
            '/users/{user}' => [
                'parameters' => [
                    [
                        'name'     => 'user',
                        'in'       => 'path',
                        'required' => true,
                        'schema'   => ['type' => 'integer'],
                    ],
                ],
                'get' => [
                    'tags'        => ['Users'],
                    'operationId' => 'showUser',
                    'summary'     => 'Show user',
                    'parameters'  => [
                        [
                            'name'        => 'include',
                            'in'          => 'query',
                            'schema'      => ['type' => ['string', 'null'], 'example' => 'profile'],
                            'description' => 'Relations to include',
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'OK',
                            'content'     => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
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
                    'tags'        => ['Users'],
                    'operationId' => 'createUser',
                    'summary'     => 'Create user',
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
        'info'    => [
            'title'   => 'Game API',
            'version' => '1.0.0',
        ],
        'host'                => 'api.example.test',
        'basePath'            => '/v1',
        'schemes'             => ['https'],
        'consumes'            => ['application/x-www-form-urlencoded'],
        'produces'            => ['application/json'],
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
                    'tags'        => ['Users'],
                    'operationId' => 'createSwaggerUser',
                    'summary'     => 'Create user',
                    'parameters'  => [
                        [
                            'name'        => 'name',
                            'in'          => 'formData',
                            'required'    => true,
                            'type'        => 'string',
                            'description' => 'Display name',
                        ],
                        [
                            'name' => 'avatar',
                            'in'   => 'formData',
                            'type' => 'file',
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Created',
                            'schema'      => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

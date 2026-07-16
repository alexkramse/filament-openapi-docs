<?php

namespace Alexkramse\FilamentOpenapiDocs\Services;

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\FilamentOpenApiDocsPlugin;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RequestSnippetPresenter
{
    /**
     * @var array<int, string>
     */
    private const array MEDIA_OR_AUTH_HEADERS = [
        'accept',
        'authorization',
        'content-type',
    ];

    public function __construct(
        private readonly ExamplePresenter $examplePresenter,
    ) {}

    /**
     * @param  array<int, string>  $servers
     * @param  array<string, mixed>  $components
     * @return array{
     *     securityItems: array<int, array<string, mixed>>,
     *     mediaHeaders: array<int, array<string, mixed>>,
     *     headerParameters: array<int, array<string, mixed>>,
     *     pathParameters: array<int, array<string, mixed>>,
     *     queryParameters: array<int, array<string, mixed>>,
     *     requests: array<int, array<string, mixed>>,
     *     messages: array<string, string>,
     *     hasRequestSamples: bool,
     * }
     */
    public function present(Endpoint $endpoint, array $servers = [], array $components = []): array
    {
        $securityItems = $this->securityItems($endpoint, $components);
        $mediaHeaders = $this->mediaHeaders($endpoint);
        $headerParameters = $this->parameters($endpoint, $components, 'header', true);
        $pathParameters = $this->parameters($endpoint, $components, 'path');
        $queryParameters = $this->parameters($endpoint, $components, 'query');
        $requestBodies = $endpoint->requestBodies === [] ? [null] : $endpoint->requestBodies;
        $requests = [];

        foreach ($requestBodies as $bodyIndex => $body) {
            $samples = is_array($body) ? $this->examplePresenter->samples($body, $components) : [[]];

            if ($samples === []) {
                $samples = [[]];
            }

            foreach ($samples as $sampleIndex => $sample) {
                $bodyText = is_string($sample['value'] ?? null) ? $sample['value'] : '';
                $requests[] = [
                    'key' => "request-{$bodyIndex}-{$sampleIndex}",
                    'label' => $this->label($body, $sample),
                    'urlTemplate' => $this->urlTemplate($endpoint, $servers),
                    'authParameters' => $this->authParameters($securityItems),
                    'mediaHeaderParameters' => $this->editableMediaHeaders($mediaHeaders),
                    'headerParameters' => $this->editableParameters($headerParameters),
                    'pathParameters' => $this->editableParameters($pathParameters),
                    'queryParameters' => $this->editableParameters($queryParameters),
                    'bodyText' => $bodyText,
                    'bodyMimeType' => is_array($body) ? $body['contentType'] : null,
                    'har' => $this->har(
                        endpoint: $endpoint,
                        servers: $servers,
                        pathParameters: $pathParameters,
                        queryParameters: $queryParameters,
                        headerParameters: $headerParameters,
                        mediaHeaders: $mediaHeaders,
                        securityItems: $securityItems,
                        body: $body,
                        bodyText: $bodyText,
                    ),
                ];
            }
        }

        return [
            'securityItems' => $securityItems,
            'mediaHeaders' => $mediaHeaders,
            'headerParameters' => $headerParameters,
            'pathParameters' => $pathParameters,
            'queryParameters' => $queryParameters,
            'requests' => $requests,
            'messages' => $this->messages(),
            'hasRequestSamples' => $this->hasRequestSamples() && $requests !== [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function securityItems(Endpoint $endpoint, array $components): array
    {
        $securitySchemes = Arr::get($components, 'securitySchemes', []);

        if (! is_array($securitySchemes)) {
            return [];
        }

        return collect($endpoint->security)
            ->filter(fn (mixed $securityRequirement): bool => is_array($securityRequirement))
            ->flatMap(function (array $securityRequirement) use ($securitySchemes): array {
                return collect(array_keys($securityRequirement))
                    ->map(fn (string|int $schemeName): ?array => $this->securityItem((string) $schemeName, $securitySchemes[(string) $schemeName] ?? null))
                    ->filter()
                    ->values()
                    ->all();
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $scheme
     * @return array<string, mixed>|null
     */
    private function securityItem(string $schemeName, mixed $scheme): ?array
    {
        if (! is_array($scheme)) {
            return null;
        }

        $type = is_string($scheme['type'] ?? null) ? Str::lower($scheme['type']) : null;
        $schemeValue = is_string($scheme['scheme'] ?? null) ? Str::lower($scheme['scheme']) : null;
        $description = isset($scheme['description']) ? (string) $scheme['description'] : null;

        if ($type === 'http' && $schemeValue === 'bearer') {
            return [
                'name' => 'Authorization',
                'label' => __('filament-openapi-docs::ui.auth.bearer'),
                'location' => 'header',
                'value' => 'Bearer <token>',
                'prefix' => 'Bearer ',
                'placeholder' => '<token>',
                'description' => $description ?? __('filament-openapi-docs::ui.auth.bearer_description'),
                'schemeType' => 'http',
                'scheme' => 'bearer',
                'documentationExample' => 'Bearer 123',
                'sendable' => true,
            ];
        }

        if ($type === 'http' && $schemeValue === 'basic') {
            return [
                'name' => 'Authorization',
                'label' => __('filament-openapi-docs::ui.auth.basic'),
                'location' => 'header',
                'value' => 'Basic <credentials>',
                'prefix' => 'Basic ',
                'placeholder' => '<credentials>',
                'description' => $description,
                'schemeType' => 'http',
                'scheme' => 'basic',
                'documentationExample' => 'Authorization: Basic <credentials>',
                'sendable' => true,
            ];
        }

        if ($type === 'http' && is_string($scheme['scheme'] ?? null)) {
            return [
                'name' => 'Authorization',
                'label' => Str::headline($scheme['scheme']),
                'location' => 'header',
                'value' => Str::headline($scheme['scheme']).' <credentials>',
                'prefix' => Str::headline($scheme['scheme']).' ',
                'placeholder' => '<credentials>',
                'description' => $description,
                'schemeType' => 'http',
                'scheme' => $schemeValue,
                'documentationExample' => 'Authorization: '.Str::headline($scheme['scheme']).' <credentials>',
                'sendable' => true,
            ];
        }

        if ($type === 'apikey' && is_string($scheme['name'] ?? null)) {
            return [
                'name' => $scheme['name'],
                'label' => $schemeName,
                'location' => is_string($scheme['in'] ?? null) ? $scheme['in'] : 'header',
                'value' => '<api-key>',
                'prefix' => '',
                'placeholder' => '<api-key>',
                'description' => $description,
                'schemeType' => 'apiKey',
                'scheme' => null,
                'documentationExample' => $scheme['name'].': <api-key>',
                'sendable' => true,
            ];
        }

        if ($type === 'mutualtls') {
            return [
                'name' => $schemeName,
                'label' => __('filament-openapi-docs::ui.auth.mutual_tls'),
                'location' => 'security',
                'value' => '<client-certificate>',
                'prefix' => '',
                'placeholder' => '<client-certificate>',
                'description' => $description,
                'schemeType' => 'mutualTLS',
                'scheme' => null,
                'documentationExample' => null,
                'sendable' => false,
            ];
        }

        if ($type === 'oauth2') {
            return [
                'name' => $schemeName,
                'label' => __('filament-openapi-docs::ui.auth.oauth2'),
                'location' => 'security',
                'value' => '<credentials>',
                'prefix' => '',
                'placeholder' => '<credentials>',
                'description' => $description,
                'schemeType' => 'oauth2',
                'scheme' => null,
                'documentationExample' => null,
                'sendable' => false,
            ];
        }

        if ($type === 'openidconnect') {
            return [
                'name' => $schemeName,
                'label' => __('filament-openapi-docs::ui.auth.openid_connect'),
                'location' => 'security',
                'value' => '<credentials>',
                'prefix' => '',
                'placeholder' => '<credentials>',
                'description' => $description,
                'schemeType' => 'openIdConnect',
                'scheme' => null,
                'documentationExample' => is_string($scheme['openIdConnectUrl'] ?? null) ? $scheme['openIdConnectUrl'] : null,
                'sendable' => false,
            ];
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parameters(Endpoint $endpoint, array $components, string $location, bool $rejectMediaOrAuthHeaders = false): array
    {
        return collect($endpoint->parameters)
            ->where('in', $location)
            ->reject(fn (array $parameter): bool => $rejectMediaOrAuthHeaders && $this->isMediaOrAuthHeader($parameter))
            ->map(fn (array $parameter): array => [
                ...$parameter,
                'value' => $this->stringValue($this->parameterValue($parameter, $components)),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $parameter
     */
    private function isMediaOrAuthHeader(array $parameter): bool
    {
        return is_string($parameter['name'] ?? null)
            && in_array(Str::lower($parameter['name']), self::MEDIA_OR_AUTH_HEADERS, true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mediaHeaders(Endpoint $endpoint): array
    {
        $headers = [];
        $contentTypes = collect($endpoint->requestBodies)
            ->pluck('contentType')
            ->filter(fn (mixed $contentType): bool => is_string($contentType) && filled($contentType))
            ->unique()
            ->values();

        if ($contentTypes->isNotEmpty()) {
            $headers[] = [
                'name' => 'Content-Type',
                'value' => $contentTypes->first(),
                'description' => __('filament-openapi-docs::ui.meta.request_body_media_type'),
            ];
        }

        if ($accept = $this->responseContentType($endpoint)) {
            $headers[] = [
                'name' => 'Accept',
                'value' => $accept,
                'description' => __('filament-openapi-docs::ui.meta.preferred_response_media_type'),
            ];
        }

        return $headers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function editableParameters(array $parameters): array
    {
        return collect($parameters)
            ->map(fn (array $parameter): array => [
                'name' => $parameter['name'],
                'value' => $parameter['value'] ?? '',
                'developerOnly' => false,
                'removable' => false,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function editableMediaHeaders(array $mediaHeaders): array
    {
        return collect($mediaHeaders)
            ->map(fn (array $header): array => [
                'name' => $header['name'],
                'value' => $header['value'],
                'description' => $header['description'],
                'developerOnly' => false,
                'removable' => false,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function authParameters(array $securityItems): array
    {
        return collect($securityItems)
            ->reject(fn (array $securityItem): bool => ! ($securityItem['sendable'] ?? false))
            ->map(fn (array $securityItem): array => [
                'location' => $securityItem['location'],
                'name' => $securityItem['name'],
                'label' => $securityItem['label'],
                'prefix' => $securityItem['prefix'],
                'placeholder' => $securityItem['placeholder'],
                'value' => '',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $pathParameters
     * @param  array<int, array<string, mixed>>  $queryParameters
     * @param  array<int, array<string, mixed>>  $headerParameters
     * @param  array<int, array<string, mixed>>  $mediaHeaders
     * @param  array<int, array<string, mixed>>  $securityItems
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    private function har(
        Endpoint $endpoint,
        array $servers,
        array $pathParameters,
        array $queryParameters,
        array $headerParameters,
        array $mediaHeaders,
        array $securityItems,
        ?array $body,
        string $bodyText,
    ): array {
        $queryString = collect($queryParameters)
            ->map(fn (array $parameter): array => ['name' => $parameter['name'], 'value' => $parameter['value'] ?? ''])
            ->values()
            ->all();
        $headers = collect([...$headerParameters, ...$mediaHeaders])
            ->map(fn (array $parameter): array => ['name' => $parameter['name'], 'value' => $parameter['value'] ?? ''])
            ->values()
            ->all();
        $cookies = [];

        $this->applySecurity($securityItems, $headers, $queryString, $cookies);

        $har = [
            'method' => $endpoint->method,
            'url' => $this->url($endpoint, $servers, $pathParameters, $queryString),
            'httpVersion' => 'HTTP/1.1',
            'headers' => array_values($headers),
            'queryString' => array_values($queryString),
            'cookies' => array_values($cookies),
        ];

        if (is_array($body) && filled($bodyText)) {
            $har['postData'] = [
                'mimeType' => $body['contentType'],
                'text' => $bodyText,
            ];
        }

        return $har;
    }

    /**
     * @param  array<int, array<string, mixed>>  $securityItems
     * @param  array<int, array{name: string, value: string}>  $headers
     * @param  array<int, array{name: string, value: string}>  $queryString
     * @param  array<int, array{name: string, value: string}>  $cookies
     */
    private function applySecurity(array $securityItems, array &$headers, array &$queryString, array &$cookies): void
    {
        foreach ($securityItems as $securityItem) {
            $value = $securityItem['value'] ?? '';
            $parameter = [
                'name' => $securityItem['name'],
                'value' => is_string($value) ? $value : '',
            ];

            match ($securityItem['location'] ?? null) {
                'query' => $queryString[] = $parameter,
                'cookie' => $cookies[] = $parameter,
                'header' => $headers = $this->replaceHeader($headers, $parameter['name'], $parameter['value']),
                default => null,
            };
        }
    }

    /**
     * @param  array<int, array{name: string, value: string}>  $headers
     * @return array<int, array{name: string, value: string}>
     */
    private function replaceHeader(array $headers, string $name, string $value): array
    {
        $headers = collect($headers)
            ->reject(fn (array $header): bool => Str::lower($header['name']) === Str::lower($name))
            ->values()
            ->all();

        $headers[] = [
            'name' => $name,
            'value' => $value,
        ];

        return $headers;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pathParameters
     * @param  array<int, array{name: string, value: string}>  $queryString
     */
    private function url(Endpoint $endpoint, array $servers, array $pathParameters, array $queryString): string
    {
        $path = preg_replace_callback('/\{([^}]+)\}/', function (array $matches) use ($pathParameters): string {
            $parameter = collect($pathParameters)
                ->first(fn (array $parameter): bool => $parameter['name'] === $matches[1]);

            return rawurlencode((string) ($parameter['value'] ?? $matches[1]));
        }, $endpoint->path) ?? $endpoint->path;

        $url = rtrim($this->server($servers), '/').'/'.ltrim($path, '/');

        if ($queryString === []) {
            return $url;
        }

        return $url.'?'.http_build_query(
            collect($queryString)->pluck('value', 'name')->all(),
            '',
            '&',
            PHP_QUERY_RFC3986,
        );
    }

    /**
     * @param  array<int, string>  $servers
     */
    private function urlTemplate(Endpoint $endpoint, array $servers): string
    {
        return rtrim($this->server($servers), '/').'/'.ltrim($endpoint->path, '/');
    }

    /**
     * @param  array<int, string>  $servers
     */
    private function server(array $servers): string
    {
        $configuredServer = FilamentOpenApiDocsPlugin::current()?->getDefaultServer()
            ?? config('filament-openapi-docs.request_samples.default_server');

        if (is_string($configuredServer) && filled($configuredServer)) {
            return $configuredServer;
        }

        return $servers[0] ?? url('/');
    }

    private function hasRequestSamples(): bool
    {
        return FilamentOpenApiDocsPlugin::current()?->hasRequestSamples()
            ?? (bool) config('filament-openapi-docs.request_samples.enabled', true);
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'invalidHeaderName' => __('filament-openapi-docs::ui.messages.invalid_header_name'),
            'jsonBeforeFormatting' => __('filament-openapi-docs::ui.messages.json_before_formatting'),
            'jsonBeforeSending' => __('filament-openapi-docs::ui.messages.json_before_sending'),
            'unableToGenerateRequestSample' => __('filament-openapi-docs::ui.messages.unable_to_generate_request_sample'),
            'unableToSendRequest' => __('filament-openapi-docs::ui.messages.unable_to_send_request'),
        ];
    }

    private function responseContentType(Endpoint $endpoint): ?string
    {
        $contentTypes = collect($endpoint->responses)
            ->filter(fn (array $response, string $status): bool => Str::startsWith($status, '2') && $response['content'] !== [])
            ->pluck('content')
            ->whenEmpty(fn ($responses) => collect($endpoint->responses)->pluck('content'))
            ->flatMap(fn (array $content): array => array_keys($content))
            ->unique()
            ->values();

        return $contentTypes->first(fn (string $contentType): bool => Str::contains($contentType, ['json', '+json']))
            ?? $contentTypes->first();
    }

    /**
     * @param  array<string, mixed>  $parameter
     * @param  array<string, mixed>  $components
     */
    private function parameterValue(array $parameter, array $components): mixed
    {
        if (array_key_exists('example', $parameter)) {
            return $parameter['example'];
        }

        if (is_array($parameter['examples'] ?? null) && $parameter['examples'] !== []) {
            return $this->exampleValue(reset($parameter['examples']));
        }

        if (array_key_exists('default', $parameter)) {
            return $parameter['default'];
        }

        $schema = $this->resolveReference(is_array($parameter['schema'] ?? null) ? $parameter['schema'] : [], $components);

        if (array_key_exists('example', $schema)) {
            return $schema['example'];
        }

        if (array_key_exists('default', $schema)) {
            return $schema['default'];
        }

        if (is_array($schema['enum'] ?? null) && $schema['enum'] !== []) {
            return $schema['enum'][0];
        }

        return match ($this->schemaType($schema)) {
            'integer' => 1,
            'number' => 1.0,
            'boolean' => true,
            default => $parameter['name'] ?? 'value',
        };
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $sample
     */
    private function label(?array $body, array $sample): string
    {
        if (! is_array($body)) {
            return 'Request';
        }

        $sampleLabel = $sample['label'] ?? __('filament-openapi-docs::ui.labels.example');

        return "{$body['contentType']} - {$sampleLabel}";
    }

    private function exampleValue(mixed $example): mixed
    {
        if (is_array($example) && array_key_exists('value', $example)) {
            return $example['value'];
        }

        return $example;
    }

    private function stringValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $components
     * @return array<string, mixed>
     */
    private function resolveReference(array $schema, array $components): array
    {
        if (! isset($schema['$ref']) || ! is_string($schema['$ref'])) {
            return $schema;
        }

        $path = Str::of($schema['$ref'])
            ->after('#/components/')
            ->replace('/', '.')
            ->toString();

        $resolved = data_get($components, $path);

        if (! is_array($resolved)) {
            return $schema;
        }

        return array_replace_recursive($resolved, array_diff_key($schema, ['$ref' => true]));
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function schemaType(array $schema): ?string
    {
        if (isset($schema['type']) && is_array($schema['type'])) {
            return collect($schema['type'])
                ->first(fn (mixed $type): bool => is_string($type) && $type !== 'null');
        }

        return is_string($schema['type'] ?? null) ? $schema['type'] : null;
    }
}

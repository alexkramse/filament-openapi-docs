<?php

namespace Alexkramse\FilamentOpenapiDocs\Services;

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Illuminate\Support\Str;

class OpenApiParser
{
    private const HTTP_METHODS = [
        'get',
        'post',
        'put',
        'patch',
        'delete',
        'options',
        'head',
        'trace',
    ];

    /**
     * @param  array<string, mixed>  $spec
     * @return array{
     *     info: array<string, mixed>,
     *     servers: array<int, string>,
     *     endpoints: array<string, array<int, Endpoint>>,
     *     endpointCount: int,
     * }
     */
    public function parse(array $spec): array
    {
        $endpoints = [];
        $globalSecurity = is_array($spec['security'] ?? null) ? $spec['security'] : [];
        $components = $this->components($spec);

        foreach (($spec['paths'] ?? []) as $path => $pathItem) {
            if (! is_array($pathItem)) {
                continue;
            }

            $pathParameters = $this->parameters($pathItem['parameters'] ?? [], ['body', 'formData']);

            foreach ($pathItem as $method => $operation) {
                if (! in_array($method, self::HTTP_METHODS, true) || ! is_array($operation)) {
                    continue;
                }

                $endpoint = $this->endpoint($method, (string) $path, $operation, $pathParameters, $globalSecurity, $spec);

                $endpoints[$endpoint->group()][] = $endpoint;
            }
        }

        ksort($endpoints);

        return [
            'info' => is_array($spec['info'] ?? null) ? $spec['info'] : [],
            'servers' => $this->servers($spec),
            'endpoints' => $endpoints,
            'endpointCount' => collect($endpoints)->flatten(1)->count(),
            'components' => $components,
        ];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<int, array{name: string, in: string, type: string, required: bool, description: ?string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>, default?: mixed}>  $pathParameters
     * @param  array<int, array<string, mixed>>  $globalSecurity
     */
    private function endpoint(string $method, string $path, array $operation, array $pathParameters, array $globalSecurity, array $spec): Endpoint
    {
        $summary = (string) ($operation['summary'] ?? $operation['operationId'] ?? '');
        $tags = array_values(array_filter(
            $operation['tags'] ?? [],
            fn (mixed $tag): bool => is_string($tag),
        ));
        $parameters = [
            ...$pathParameters,
            ...$this->parameters($operation['parameters'] ?? [], ['body', 'formData']),
        ];

        return new Endpoint(
            id: Str::slug("{$method}-{$path}"),
            method: Str::upper($method),
            path: $path,
            summary: $summary,
            description: isset($operation['description']) ? (string) $operation['description'] : null,
            tags: $tags,
            parameters: $parameters,
            requestBodies: $this->requestBodies($operation, $spec),
            responses: $this->responses($operation['responses'] ?? []),
            security: is_array($operation['security'] ?? null) ? $operation['security'] : $globalSecurity,
            deprecated: (bool) ($operation['deprecated'] ?? false),
        );
    }

    /**
     * @param  array<int, string>  $excludedLocations
     * @return array<int, array{name: string, in: string, type: string, required: bool, description: ?string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>, default?: mixed}>
     */
    private function parameters(mixed $parameters, array $excludedLocations = []): array
    {
        if (! is_array($parameters)) {
            return [];
        }

        return collect($parameters)
            ->filter(fn (mixed $parameter): bool => is_array($parameter))
            ->reject(fn (array $parameter): bool => in_array((string) ($parameter['in'] ?? ''), $excludedLocations, true))
            ->map(function (array $parameter): array {
                $schema = is_array($parameter['schema'] ?? null) ? $parameter['schema'] : [];

                return [
                    'name' => (string) ($parameter['name'] ?? ''),
                    'in' => (string) ($parameter['in'] ?? ''),
                    'type' => $this->schemaLabel($schema),
                    'required' => (bool) ($parameter['required'] ?? false),
                    'description' => isset($parameter['description']) ? (string) $parameter['description'] : null,
                    'schema' => $schema,
                    ...(array_key_exists('example', $parameter) ? ['example' => $parameter['example']] : []),
                    'examples' => is_array($parameter['examples'] ?? null) ? $parameter['examples'] : [],
                    ...(array_key_exists('default', $schema) ? ['default' => $schema['default']] : []),
                ];
            })
            ->filter(fn (array $parameter): bool => $parameter['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{contentType: string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>}>
     */
    private function requestBodies(array $operation, array $spec): array
    {
        $requestBody = $operation['requestBody'] ?? [];

        if (! is_array($requestBody) || ! is_array($requestBody['content'] ?? null)) {
            return $this->swaggerRequestBodies($operation, $spec);
        }

        return collect($requestBody['content'])
            ->filter(fn (mixed $content): bool => is_array($content))
            ->map(fn (array $content, string $contentType): array => [
                'contentType' => $contentType,
                'schema' => is_array($content['schema'] ?? null) ? $content['schema'] : [],
                ...(array_key_exists('example', $content) ? ['example' => $content['example']] : []),
                'examples' => is_array($content['examples'] ?? null) ? $content['examples'] : [],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{contentType: string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>}>
     */
    private function swaggerRequestBodies(array $operation, array $spec): array
    {
        $parameters = collect($operation['parameters'] ?? [])
            ->filter(fn (mixed $parameter): bool => is_array($parameter));
        $bodyParameter = $parameters->first(fn (array $parameter): bool => ($parameter['in'] ?? null) === 'body');

        if (is_array($bodyParameter)) {
            return [[
                'contentType' => $this->consumes($operation, $spec)[0] ?? 'application/json',
                'schema' => is_array($bodyParameter['schema'] ?? null) ? $bodyParameter['schema'] : [],
                ...(array_key_exists('example', $bodyParameter) ? ['example' => $bodyParameter['example']] : []),
                'examples' => [],
            ]];
        }

        $formParameters = $parameters
            ->filter(fn (array $parameter): bool => ($parameter['in'] ?? null) === 'formData')
            ->values();

        if ($formParameters->isEmpty()) {
            return [];
        }

        $required = $formParameters
            ->filter(fn (array $parameter): bool => (bool) ($parameter['required'] ?? false))
            ->pluck('name')
            ->filter(fn (mixed $name): bool => is_string($name) && $name !== '')
            ->values()
            ->all();
        $properties = $formParameters
            ->mapWithKeys(function (array $parameter): array {
                $schema = is_array($parameter['schema'] ?? null)
                    ? $parameter['schema']
                    : ['type' => is_string($parameter['type'] ?? null) ? $parameter['type'] : 'string'];

                return [(string) ($parameter['name'] ?? '') => array_filter([
                    ...$schema,
                    'description' => isset($parameter['description']) ? (string) $parameter['description'] : null,
                    ...(array_key_exists('example', $parameter) ? ['example' => $parameter['example']] : []),
                ], fn (mixed $value): bool => $value !== null)];
            })
            ->filter(fn (array $schema, string $name): bool => $name !== '')
            ->all();

        return [[
            'contentType' => $this->formDataContentType($operation, $spec, $formParameters->all()),
            'schema' => array_filter([
                'type' => 'object',
                'required' => $required,
                'properties' => $properties,
            ]),
            'examples' => [],
        ]];
    }

    /**
     * @return array<string, array{description: ?string, content: array<string, array{contentType: string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>}>}>
     */
    private function responses(mixed $responses): array
    {
        if (! is_array($responses)) {
            return [];
        }

        return collect($responses)
            ->filter(fn (mixed $response): bool => is_array($response))
            ->mapWithKeys(fn (array $response, string|int $status): array => [
                (string) $status => [
                    'description' => isset($response['description']) ? (string) $response['description'] : null,
                    'content' => $this->responseContent($response['content'] ?? []),
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, array{contentType: string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>}>
     */
    private function responseContent(mixed $content): array
    {
        if (! is_array($content)) {
            return [];
        }

        return collect($content)
            ->filter(fn (mixed $mediaType): bool => is_array($mediaType))
            ->mapWithKeys(fn (array $mediaType, string $contentType): array => [
                $contentType => [
                    'contentType' => $contentType,
                    'schema' => is_array($mediaType['schema'] ?? null) ? $mediaType['schema'] : [],
                    ...(array_key_exists('example', $mediaType) ? ['example' => $mediaType['example']] : []),
                    'examples' => is_array($mediaType['examples'] ?? null) ? $mediaType['examples'] : [],
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function servers(array $spec): array
    {
        $servers = $spec['servers'] ?? [];

        if (! is_array($servers)) {
            return $this->swaggerServers($spec);
        }

        $openApiServers = collect($servers)
            ->filter(fn (mixed $server): bool => is_array($server))
            ->pluck('url')
            ->filter(fn (mixed $url): bool => is_string($url))
            ->values()
            ->all();

        return $openApiServers !== [] ? $openApiServers : $this->swaggerServers($spec);
    }

    /**
     * @return array<int, string>
     */
    private function swaggerServers(array $spec): array
    {
        if (! is_string($spec['host'] ?? null) || $spec['host'] === '') {
            return [];
        }

        $scheme = collect($spec['schemes'] ?? [])
            ->first(fn (mixed $scheme): bool => is_string($scheme) && $scheme !== '') ?? 'https';
        $basePath = is_string($spec['basePath'] ?? null) ? $spec['basePath'] : '';

        return [rtrim($scheme.'://'.$spec['host'], '/').'/'.ltrim($basePath, '/')];
    }

    /**
     * @return array<string, mixed>
     */
    private function components(array $spec): array
    {
        if (is_array($spec['components'] ?? null)) {
            return $spec['components'];
        }

        $securityDefinitions = is_array($spec['securityDefinitions'] ?? null) ? $spec['securityDefinitions'] : [];

        if ($securityDefinitions === []) {
            return [];
        }

        return [
            'securitySchemes' => collect($securityDefinitions)
                ->filter(fn (mixed $scheme): bool => is_array($scheme))
                ->map(fn (array $scheme): array => $this->swaggerSecurityScheme($scheme))
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $scheme
     * @return array<string, mixed>
     */
    private function swaggerSecurityScheme(array $scheme): array
    {
        if (($scheme['type'] ?? null) === 'basic') {
            return [
                ...$scheme,
                'type' => 'http',
                'scheme' => 'basic',
            ];
        }

        return $scheme;
    }

    /**
     * @return array<int, string>
     */
    private function consumes(array $operation, array $spec): array
    {
        return collect($operation['consumes'] ?? $spec['consumes'] ?? [])
            ->filter(fn (mixed $contentType): bool => is_string($contentType) && $contentType !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $formParameters
     */
    private function formDataContentType(array $operation, array $spec, array $formParameters): string
    {
        $consumes = $this->consumes($operation, $spec);

        if ($consumes !== []) {
            return $consumes[0];
        }

        $hasFile = collect($formParameters)
            ->contains(fn (array $parameter): bool => ($parameter['type'] ?? null) === 'file');

        return $hasFile ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
    }

    private function schemaLabel(mixed $schema): string
    {
        if (! is_array($schema)) {
            return 'mixed';
        }

        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            return Str::afterLast($schema['$ref'], '/');
        }

        if (isset($schema['type']) && is_array($schema['type'])) {
            return collect($schema['type'])
                ->first(fn (mixed $type): bool => is_string($type) && $type !== 'null') ?? 'mixed';
        }

        if (isset($schema['type']) && is_string($schema['type'])) {
            return $schema['type'];
        }

        return 'mixed';
    }
}

<?php

namespace Kramarenko\FilamentOpenApiDocs\Services;

use Illuminate\Support\Str;
use Kramarenko\FilamentOpenApiDocs\DTO\Endpoint;

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

        foreach (($spec['paths'] ?? []) as $path => $pathItem) {
            if (! is_array($pathItem)) {
                continue;
            }

            $pathParameters = $this->parameters($pathItem['parameters'] ?? []);

            foreach ($pathItem as $method => $operation) {
                if (! in_array($method, self::HTTP_METHODS, true) || ! is_array($operation)) {
                    continue;
                }

                $endpoint = $this->endpoint($method, (string) $path, $operation, $pathParameters, $globalSecurity);

                $endpoints[$endpoint->group()][] = $endpoint;
            }
        }

        ksort($endpoints);

        return [
            'info' => is_array($spec['info'] ?? null) ? $spec['info'] : [],
            'servers' => $this->servers($spec['servers'] ?? []),
            'endpoints' => $endpoints,
            'endpointCount' => collect($endpoints)->flatten(1)->count(),
            'components' => $spec['components'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<int, array{name: string, in: string, type: string, required: bool, description: ?string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>, default?: mixed}>  $pathParameters
     * @param  array<int, array<string, mixed>>  $globalSecurity
     */
    private function endpoint(string $method, string $path, array $operation, array $pathParameters, array $globalSecurity): Endpoint
    {
        $summary = (string) ($operation['summary'] ?? $operation['operationId'] ?? '');
        $tags = array_values(array_filter(
            $operation['tags'] ?? [],
            fn (mixed $tag): bool => is_string($tag),
        ));
        $parameters = [
            ...$pathParameters,
            ...$this->parameters($operation['parameters'] ?? []),
        ];

        return new Endpoint(
            id: Str::slug("{$method}-{$path}"),
            method: Str::upper($method),
            path: $path,
            summary: $summary,
            description: isset($operation['description']) ? (string) $operation['description'] : null,
            tags: $tags,
            parameters: $parameters,
            requestBodies: $this->requestBodies($operation['requestBody'] ?? []),
            responses: $this->responses($operation['responses'] ?? []),
            security: is_array($operation['security'] ?? null) ? $operation['security'] : $globalSecurity,
            deprecated: (bool) ($operation['deprecated'] ?? false),
        );
    }

    /**
     * @return array<int, array{name: string, in: string, type: string, required: bool, description: ?string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>, default?: mixed}>
     */
    private function parameters(mixed $parameters): array
    {
        if (! is_array($parameters)) {
            return [];
        }

        return collect($parameters)
            ->filter(fn (mixed $parameter): bool => is_array($parameter))
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
    private function requestBodies(mixed $requestBody): array
    {
        if (! is_array($requestBody) || ! is_array($requestBody['content'] ?? null)) {
            return [];
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
    private function servers(mixed $servers): array
    {
        if (! is_array($servers)) {
            return [];
        }

        return collect($servers)
            ->filter(fn (mixed $server): bool => is_array($server))
            ->pluck('url')
            ->filter(fn (mixed $url): bool => is_string($url))
            ->values()
            ->all();
    }

    private function schemaLabel(mixed $schema): string
    {
        if (! is_array($schema)) {
            return 'mixed';
        }

        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            return Str::afterLast($schema['$ref'], '/');
        }

        if (isset($schema['type']) && is_string($schema['type'])) {
            return $schema['type'];
        }

        return 'mixed';
    }
}

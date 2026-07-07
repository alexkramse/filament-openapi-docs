<?php

namespace Kramarenko\FilamentOpenApiDocs\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Kramarenko\FilamentOpenApiDocs\DTO\Endpoint;

class RequestSnippetPresenter
{
    public function __construct(
        private readonly ExamplePresenter $examplePresenter,
    ) {}

    /**
     * @param  array<int, string>  $servers
     * @param  array<string, mixed>  $components
     * @return array{requests: array<int, array{key: string, label: string, har: array<string, mixed>}>}
     */
    public function present(Endpoint $endpoint, array $servers = [], array $components = []): array
    {
        $requestBodies = $endpoint->requestBodies === [] ? [null] : $endpoint->requestBodies;
        $requests = [];

        foreach ($requestBodies as $bodyIndex => $body) {
            $samples = is_array($body) ? $this->examplePresenter->samples($body, $components) : [[]];

            if ($samples === []) {
                $samples = [[]];
            }

            foreach ($samples as $sampleIndex => $sample) {
                $requests[] = [
                    'key' => "request-{$bodyIndex}-{$sampleIndex}",
                    'label' => $this->label($body, $sample),
                    'har' => $this->har($endpoint, $servers, $components, $body, $sample),
                ];
            }
        }

        return [
            'requests' => $requests,
        ];
    }

    /**
     * @param  array<string, mixed>  $components
     * @param  array<string, mixed>|null  $body
     * @param  array<string, string>  $sample
     * @return array<string, mixed>
     */
    private function har(Endpoint $endpoint, array $servers, array $components, ?array $body, array $sample): array
    {
        $queryString = $this->parameters($endpoint, $components, 'query');
        $headers = $this->parameters($endpoint, $components, 'header');
        $cookies = [];

        $this->applySecurity($endpoint, $components, $headers, $queryString, $cookies);

        if (is_array($body)) {
            $headers = $this->replaceHeader($headers, 'Content-Type', $body['contentType']);
        }

        $har = [
            'method' => $endpoint->method,
            'url' => $this->url($endpoint, $servers, $components, $queryString),
            'httpVersion' => 'HTTP/1.1',
            'headers' => array_values($headers),
            'queryString' => array_values($queryString),
            'cookies' => array_values($cookies),
        ];

        if (is_array($body) && filled($sample['value'] ?? null)) {
            $har['postData'] = [
                'mimeType' => $body['contentType'],
                'text' => $sample['value'],
            ];
        }

        return $har;
    }

    /**
     * @param  array<string, mixed>  $components
     * @return array<int, array{name: string, value: string}>
     */
    private function parameters(Endpoint $endpoint, array $components, string $location): array
    {
        return collect($endpoint->parameters)
            ->where('in', $location)
            ->map(fn (array $parameter): array => [
                'name' => $parameter['name'],
                'value' => $this->stringValue($this->parameterValue($parameter, $components)),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $components
     * @param  array<int, array{name: string, value: string}>  $queryString
     */
    private function url(Endpoint $endpoint, array $servers, array $components, array $queryString): string
    {
        $path = preg_replace_callback('/\{([^}]+)\}/', function (array $matches) use ($endpoint, $components): string {
            $parameter = collect($endpoint->parameters)
                ->first(fn (array $parameter): bool => $parameter['in'] === 'path' && $parameter['name'] === $matches[1]);

            return rawurlencode($this->stringValue($this->parameterValue(is_array($parameter) ? $parameter : [], $components)));
        }, $endpoint->path) ?? $endpoint->path;

        $server = $this->server($servers);
        $url = rtrim($server, '/').'/'.ltrim($path, '/');

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
    private function server(array $servers): string
    {
        $configuredServer = config('filament-openapi-docs.request_samples.default_server');

        if (is_string($configuredServer) && filled($configuredServer)) {
            return $configuredServer;
        }

        return $servers[0] ?? url('/');
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
     * @param  array<int, array{name: string, value: string}>  $headers
     * @param  array<int, array{name: string, value: string}>  $queryString
     * @param  array<int, array{name: string, value: string}>  $cookies
     */
    private function applySecurity(Endpoint $endpoint, array $components, array &$headers, array &$queryString, array &$cookies): void
    {
        $securitySchemes = Arr::get($components, 'securitySchemes', []);

        if (! is_array($securitySchemes)) {
            return;
        }

        foreach ($endpoint->security as $securityRequirement) {
            if (! is_array($securityRequirement)) {
                continue;
            }

            foreach (array_keys($securityRequirement) as $schemeName) {
                $scheme = $securitySchemes[$schemeName] ?? null;

                if (! is_array($scheme)) {
                    continue;
                }

                if (($scheme['type'] ?? null) === 'http' && ($scheme['scheme'] ?? null) === 'bearer') {
                    $headers = $this->replaceHeader($headers, 'Authorization', 'Bearer <token>');

                    continue;
                }

                if (($scheme['type'] ?? null) === 'http' && ($scheme['scheme'] ?? null) === 'basic') {
                    $headers = $this->replaceHeader($headers, 'Authorization', 'Basic <credentials>');

                    continue;
                }

                if (($scheme['type'] ?? null) !== 'apiKey' || ! is_string($scheme['name'] ?? null)) {
                    continue;
                }

                $apiKey = ['name' => $scheme['name'], 'value' => '<api-key>'];

                match ($scheme['in'] ?? null) {
                    'query' => $queryString[] = $apiKey,
                    'cookie' => $cookies[] = $apiKey,
                    default => $headers = $this->replaceHeader($headers, $apiKey['name'], $apiKey['value']),
                };
            }
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
     * @param  array<string, mixed>|null  $body
     * @param  array<string, string>  $sample
     */
    private function label(?array $body, array $sample): string
    {
        if (! is_array($body)) {
            return 'Request';
        }

        $sampleLabel = $sample['label'] ?? 'Example';

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

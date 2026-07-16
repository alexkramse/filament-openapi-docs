<?php

namespace Alexkramse\FilamentOpenapiDocs\Services;

use Illuminate\Support\Str;

class ExamplePresenter
{
    /**
     * @param  array{contentType: string, schema: array<string, mixed>, example?: mixed, examples?: array<mixed>}  $mediaType
     * @param  array<string, mixed>  $components
     * @return array<int, array{label: string, value: string}>
     */
    public function samples(array $mediaType, array $components = []): array
    {
        $samples = [];

        if (array_key_exists('example', $mediaType)) {
            $samples[] = [
                'label' => __('filament-openapi-docs::ui.labels.example'),
                'value' => $this->format($mediaType['example'], $mediaType['contentType']),
            ];
        }

        if (is_array($mediaType['examples'] ?? null)) {
            foreach ($mediaType['examples'] as $key => $example) {
                $samples[] = [
                    'label' => $this->exampleLabel($key, $example),
                    'value' => $this->format($this->exampleValue($example), $mediaType['contentType']),
                ];
            }
        }

        if ($samples !== []) {
            return $samples;
        }

        $sample = $this->schemaSample($mediaType['schema'], $components);

        if ($sample === null) {
            return [];
        }

        return [[
            'label' => 'Generated',
            'value' => $this->format($sample, $mediaType['contentType']),
        ]];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $components
     */
    private function schemaSample(array $schema, array $components): mixed
    {
        $schema = $this->resolveReference($schema, $components);

        if (array_key_exists('example', $schema)) {
            return $schema['example'];
        }

        if (is_array($schema['examples'] ?? null) && $schema['examples'] !== []) {
            return $this->exampleValue(reset($schema['examples']));
        }

        if (array_key_exists('default', $schema)) {
            return $schema['default'];
        }

        if (is_array($schema['enum'] ?? null) && $schema['enum'] !== []) {
            return $schema['enum'][0];
        }

        foreach (['allOf', 'oneOf', 'anyOf'] as $composition) {
            if (! is_array($schema[$composition] ?? null)) {
                continue;
            }

            $samples = collect($schema[$composition])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): mixed => $this->schemaSample($item, $components))
                ->filter(fn (mixed $value): bool => $value !== null)
                ->values();

            if ($samples->isEmpty()) {
                return null;
            }

            if ($composition === 'allOf') {
                return $samples->reduce(
                    fn (mixed $carry, mixed $value): mixed => is_array($carry) && is_array($value)
                        ? array_replace_recursive($carry, $value)
                        : $value,
                    [],
                );
            }

            return $samples->first();
        }

        $type = $this->schemaType($schema);

        if ($type === 'array') {
            $item = is_array($schema['items'] ?? null) ? $this->schemaSample($schema['items'], $components) : null;

            return $item === null ? [] : [$item];
        }

        if ($type === 'object' || is_array($schema['properties'] ?? null)) {
            if (! is_array($schema['properties'] ?? null)) {
                return [];
            }

            return collect($schema['properties'])
                ->filter(fn (mixed $property): bool => is_array($property))
                ->map(fn (array $property): mixed => $this->schemaSample($property, $components))
                ->all();
        }

        return match ($type) {
            'integer' => 1,
            'number' => 1.0,
            'boolean' => true,
            'string' => $this->stringSample($schema),
            default => null,
        };
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

    /**
     * @param  array<string, mixed>  $schema
     */
    private function stringSample(array $schema): string
    {
        return match ($schema['format'] ?? null) {
            'date' => '2026-07-06',
            'date-time' => '2026-07-06T12:00:00Z',
            'email' => 'user@example.com',
            'uuid' => '00000000-0000-0000-0000-000000000000',
            default => 'string',
        };
    }

    private function format(mixed $value, string $contentType): string
    {
        if (Str::contains($contentType, ['json', '+json'])) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        if ($contentType === 'application/x-www-form-urlencoded' && is_array($value)) {
            return http_build_query($value, '', '&', PHP_QUERY_RFC3986);
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function exampleLabel(string|int $key, mixed $example): string
    {
        if (is_array($example) && filled($example['summary'] ?? null)) {
            return (string) $example['summary'];
        }

        return is_string($key) ? Str::headline($key) : __('filament-openapi-docs::ui.labels.example').' '.($key + 1);
    }

    private function exampleValue(mixed $example): mixed
    {
        if (is_array($example) && array_key_exists('value', $example)) {
            return $example['value'];
        }

        return $example;
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
}

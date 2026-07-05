<?php

namespace Kramarenko\FilamentOpenApiDocs\Services;

use Illuminate\Support\Str;

class SchemaPresenter
{
    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $components
     * @return array<int, array{
     *     name: string,
     *     types: array<int, string>,
     *     required: bool,
     *     description: ?string,
     *     enum: array<int, string>,
     *     examples: array<int, string>,
     *     children: array<int, mixed>,
     * }>
     */
    public function rows(array $schema, array $components = []): array
    {
        return $this->properties($this->resolveReference($schema, $components), $schema['required'] ?? [], $components);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $components
     */
    public function label(array $schema, array $components = []): string
    {
        $schema = $this->resolveReference($schema, $components);

        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            return Str::afterLast($schema['$ref'], '/');
        }

        if (($schema['type'] ?? null) === 'array') {
            return 'array<'.$this->label(is_array($schema['items'] ?? null) ? $schema['items'] : [], $components).'>';
        }

        if (isset($schema['type']) && is_string($schema['type'])) {
            return $schema['type'];
        }

        if (isset($schema['anyOf']) && is_array($schema['anyOf'])) {
            return collect($schema['anyOf'])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): string => $this->label($item, $components))
                ->implode(' | ');
        }

        if (isset($schema['oneOf']) && is_array($schema['oneOf'])) {
            return collect($schema['oneOf'])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): string => $this->label($item, $components))
                ->implode(' | ');
        }

        if (isset($schema['allOf']) && is_array($schema['allOf'])) {
            return collect($schema['allOf'])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): string => $this->label($item, $components))
                ->implode(' & ');
        }

        return 'object';
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $components
     * @return array<int, array{
     *     name: string,
     *     types: array<int, string>,
     *     required: bool,
     *     description: ?string,
     *     enum: array<int, string>,
     *     examples: array<int, string>,
     *     children: array<int, mixed>,
     * }>
     */
    private function properties(array $schema, mixed $required = [], array $components = []): array
    {
        $schema = $this->resolveReference($schema, $components);

        if (($schema['type'] ?? null) === 'array' && is_array($schema['items'] ?? null)) {
            $items = $this->resolveReference($schema['items'], $components);

            return [[
                'name' => 'items',
                'types' => $this->types($items, $components),
                'required' => false,
                'description' => isset($items['description']) ? (string) $items['description'] : null,
                'enum' => $this->enum($items),
                'examples' => $this->examples($items),
                'children' => $this->properties($items, $items['required'] ?? [], $components),
            ]];
        }

        if (! is_array($schema['properties'] ?? null)) {
            return [];
        }

        $required = is_array($required) ? $required : [];

        return collect($schema['properties'])
            ->filter(fn (mixed $property): bool => is_array($property))
            ->map(function (array $property, string $name) use ($components, $required): array {
                $resolvedProperty = $this->resolveReference($property, $components);

                return [
                    'name' => $name,
                    'types' => $this->types($resolvedProperty, $components),
                    'required' => in_array($name, $required, true),
                    'description' => isset($resolvedProperty['description']) ? (string) $resolvedProperty['description'] : null,
                    'enum' => $this->enum($resolvedProperty),
                    'examples' => $this->examples($resolvedProperty),
                    'children' => $this->properties($resolvedProperty, $resolvedProperty['required'] ?? [], $components),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $components
     * @return array<int, string>
     */
    private function types(array $schema, array $components): array
    {
        $schema = $this->resolveReference($schema, $components);

        if (isset($schema['type']) && is_array($schema['type'])) {
            return collect($schema['type'])
                ->filter(fn (mixed $type): bool => is_string($type))
                ->values()
                ->all();
        }

        return collect(explode('|', str_replace([' & ', ' | '], '|', $this->label($schema, $components))))
            ->map(fn (string $type): string => trim($type))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, string>
     */
    private function enum(array $schema): array
    {
        if (! is_array($schema['enum'] ?? null)) {
            return [];
        }

        return collect($schema['enum'])
            ->map(fn (mixed $value): string => (string) $value)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, string>
     */
    private function examples(array $schema): array
    {
        if (array_key_exists('example', $schema)) {
            return [$this->stringValue($schema['example'])];
        }

        if (! is_array($schema['examples'] ?? null)) {
            return [];
        }

        return collect($schema['examples'])
            ->map(fn (mixed $value): string => $this->stringValue(is_array($value) && array_key_exists('value', $value) ? $value['value'] : $value))
            ->values()
            ->all();
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

    private function stringValue(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }
}

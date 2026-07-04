<?php

namespace Kramarenko\FilamentOpenApiDocs\Services;

use Illuminate\Support\Str;

class SchemaPresenter
{
    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, array{
     *     name: string,
     *     type: string,
     *     required: bool,
     *     description: ?string,
     *     enum: array<int, string>,
     *     children: array<int, mixed>,
     * }>
     */
    public function rows(array $schema): array
    {
        return $this->properties($schema, $schema['required'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function label(array $schema): string
    {
        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            return Str::afterLast($schema['$ref'], '/');
        }

        if (($schema['type'] ?? null) === 'array') {
            return 'array<'.$this->label(is_array($schema['items'] ?? null) ? $schema['items'] : []).'>';
        }

        if (isset($schema['type']) && is_string($schema['type'])) {
            return $schema['type'];
        }

        if (isset($schema['anyOf']) && is_array($schema['anyOf'])) {
            return collect($schema['anyOf'])
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): string => $this->label($item))
                ->implode(' | ');
        }

        return 'object';
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  mixed  $required
     * @return array<int, array{
     *     name: string,
     *     type: string,
     *     required: bool,
     *     description: ?string,
     *     enum: array<int, string>,
     *     children: array<int, mixed>,
     * }>
     */
    private function properties(array $schema, mixed $required = []): array
    {
        if (($schema['type'] ?? null) === 'array' && is_array($schema['items'] ?? null)) {
            return [[
                'name' => 'items',
                'type' => $this->label($schema['items']),
                'required' => false,
                'description' => isset($schema['items']['description']) ? (string) $schema['items']['description'] : null,
                'enum' => $this->enum($schema['items']),
                'children' => $this->properties($schema['items'], $schema['items']['required'] ?? []),
            ]];
        }

        if (! is_array($schema['properties'] ?? null)) {
            return [];
        }

        $required = is_array($required) ? $required : [];

        return collect($schema['properties'])
            ->filter(fn (mixed $property): bool => is_array($property))
            ->map(fn (array $property, string $name): array => [
                'name' => $name,
                'type' => $this->label($property),
                'required' => in_array($name, $required, true),
                'description' => isset($property['description']) ? (string) $property['description'] : null,
                'enum' => $this->enum($property),
                'children' => $this->properties($property, $property['required'] ?? []),
            ])
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
}

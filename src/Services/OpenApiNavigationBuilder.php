<?php

namespace Kramarenko\FilamentOpenApiDocs\Services;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Kramarenko\FilamentOpenApiDocs\DTO\Endpoint;

class OpenApiNavigationBuilder
{
    /**
     * @param  array<string, array<int, Endpoint>>  $endpoints
     * @return array<int, NavigationGroup>
     */
    public function build(array $endpoints): array
    {
        return collect($endpoints)
            ->map(fn (array $groupEndpoints, string $group): NavigationGroup => NavigationGroup::make($group)
                ->collapsible()
                ->items($this->items($groupEndpoints)))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, Endpoint>  $endpoints
     * @return array<int, NavigationItem>
     */
    private function items(array $endpoints): array
    {
        return collect($endpoints)
            ->map(fn (Endpoint $endpoint): NavigationItem => NavigationItem::make($endpoint->path)
                ->url("#{$endpoint->id}")
                ->badge($endpoint->method, $this->methodColor($endpoint->method)))
            ->values()
            ->all();
    }

    private function methodColor(string $method): string
    {
        return match ($method) {
            'GET' => 'success',
            'POST' => 'info',
            'PUT', 'PATCH' => 'warning',
            'DELETE' => 'danger',
            default => 'gray',
        };
    }
}

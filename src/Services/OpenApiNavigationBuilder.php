<?php

namespace Kramarenko\FilamentOpenApiDocs\Services;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Kramarenko\FilamentOpenApiDocs\DTO\Endpoint;

class OpenApiNavigationBuilder
{
    /**
     * @param  array<string, array<int, Endpoint>>  $endpoints
     * @return array<int, NavigationItem>
     */
    public function build(array $endpoints, ?string $selectedEndpointId = null): array
    {
        return collect($endpoints)
            ->map(fn (array $groupEndpoints, string $group): NavigationGroup => NavigationGroup::make($group)
                ->label(fn ($label) => $group.' '.count($this->items($groupEndpoints, $selectedEndpointId)))
                ->collapsible()
                ->items($this->items($groupEndpoints, $selectedEndpointId)))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, Endpoint>  $endpoints
     * @return array<int, NavigationItem>
     */
    private function items(array $endpoints, ?string $selectedEndpointId): array
    {
        return collect($endpoints)
            ->map(fn (Endpoint $endpoint): NavigationItem => NavigationItem::make($endpoint->path)
                ->url('#')
                ->isActiveWhen(fn (): bool => $endpoint->id === $selectedEndpointId)
                ->badge($endpoint->method, $this->methodColor($endpoint->method))
                ->extraAttributes([
                    'wire:click.prevent' => "selectEndpoint('{$endpoint->id}')",
                ]))
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

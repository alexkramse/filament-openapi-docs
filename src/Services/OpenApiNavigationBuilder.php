<?php

namespace Alexkramse\FilamentOpenapiDocs\Services;

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\Enums\HttpMethod;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

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
                ->badge($endpoint->method, HttpMethod::color($endpoint->method))
                ->extraAttributes([
                    'wire:click.prevent' => "selectEndpoint('{$endpoint->id}')",
                ]))
            ->values()
            ->all();
    }
}

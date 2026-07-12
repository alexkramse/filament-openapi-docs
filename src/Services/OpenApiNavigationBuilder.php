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
                ->label(fn ($label) => $group)
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
            ->map(fn (Endpoint $endpoint): NavigationItem => NavigationItem::make($this->label($endpoint))
                ->url('#')
                ->isActiveWhen(fn (): bool => $endpoint->id === $selectedEndpointId)
                ->badge($endpoint->method, HttpMethod::color($endpoint->method))
                ->extraAttributes([
                    'class' => 'foad-endpoint-navigation-item',
                    'wire:click.prevent' => "selectEndpoint('{$endpoint->id}')",
                ]))
            ->values()
            ->all();
    }

    private function label(Endpoint $endpoint): string
    {
        if ($endpoint->summary === '') {
            return $endpoint->path;
        }

        return "{$endpoint->summary}\n{$endpoint->path}";
    }
}

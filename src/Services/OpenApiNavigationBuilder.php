<?php

namespace Alexkramse\FilamentOpenapiDocs\Services;

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\Enums\HttpMethod;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Js;

class OpenApiNavigationBuilder
{
    /**
     * @param  array<string, array<int, Endpoint>>  $endpoints
     * @return array<int, NavigationGroup>
     */
    public function build(array $endpoints, ?string $selectedEndpointId = null): array
    {
        $navigationGroups = [];
        $index = 0;

        foreach ($endpoints as $group => $groupEndpoints) {
            $navigationGroup = NavigationGroup::make($group)
                ->label(fn ($label) => $group)
                ->collapsible()
                ->items($this->items($groupEndpoints, $selectedEndpointId));

            if ($index > 0) {
                $navigationGroup->collapsed();
            }

            $navigationGroups[] = $navigationGroup;
            $index++;
        }

        return $navigationGroups;
    }

    /**
     * @param  array<int, Endpoint>  $endpoints
     * @return array<int, NavigationItem>
     */
    private function items(array $endpoints, ?string $selectedEndpointId): array
    {
        return collect($endpoints)
            ->map(fn (Endpoint $endpoint): NavigationItem => NavigationItem::make($endpoint->title())
                ->url($this->url($endpoint))
                ->isActiveWhen(fn (): bool => $endpoint->id === $selectedEndpointId)
                ->badge($endpoint->method, HttpMethod::color($endpoint->method))
                ->extraAttributes([
                    'wire:click.prevent' => 'selectEndpoint('.Js::from($endpoint->id).')',
                ])
            )
            ->values()
            ->all();
    }

    private function url(Endpoint $endpoint): string
    {
        return '?endpoint='.rawurlencode($endpoint->id);
    }
}

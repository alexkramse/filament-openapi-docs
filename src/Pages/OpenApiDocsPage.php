<?php

namespace Alexkramse\FilamentOpenapiDocs\Pages;

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiNavigationBuilder;
use Alexkramse\FilamentOpenapiDocs\Services\OpenApiParser;
use Alexkramse\FilamentOpenapiDocs\Support\SpecProvider;
use BackedEnum;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

class OpenApiDocsPage extends Page
{
    protected static ?string $slug = 'api-docs';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected string $view = 'filament-openapi-docs::pages.openapi-docs';

    /**
     * @var array{
     *     info: array<string, mixed>,
     *     servers: array<int, string>,
     *     endpoints: array<string, array<int, Endpoint>>,
     *     endpointCount: int,
     *     components: array<string, mixed>,
     * }|null
     */
    private ?array $openApiData = null;

    #[Url(as: 'endpoint')]
    public ?string $selectedEndpointId = null;

    public function mount(): void
    {
        $this->ensureSelectedEndpoint();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        if (config('filament-openapi-docs.layout.full_width', true)) {
            return Width::Full;
        }

        return parent::getMaxContentWidth();
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-openapi-docs.navigation.label', 'API Docs');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('filament-openapi-docs.navigation.icon', 'heroicon-o-document-text');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('filament-openapi-docs.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config('filament-openapi-docs.navigation.sort', 100);
    }

    public function selectEndpoint(string $endpointId): void
    {
        if (! $this->endpoints()->contains(fn (Endpoint $endpoint): bool => $endpoint->id === $endpointId)) {
            return;
        }

        $this->selectedEndpointId = $endpointId;
    }

    /**
     * @return array<int, NavigationGroup>
     */
    public function getSubNavigation(): array
    {
        $this->ensureSelectedEndpoint();

        return app(OpenApiNavigationBuilder::class)->build(
            endpoints: $this->openApiData()['endpoints'],
            selectedEndpointId: $this->selectedEndpointId,
        );
    }

    /**
     * @return array<int, string>
     */
    public function getPageClasses(): array
    {
        return [
            'foad-openapi-docs-page',
        ];
    }

    /**
     * @return array{
     *     info: array<string, mixed>,
     *     servers: array<int, string>,
     *     endpoints: array<string, array<int, Endpoint>>,
     *     endpointCount: int,
     *     components: array<string, mixed>,
     * }
     */
    protected function getViewData(): array
    {
        $this->ensureSelectedEndpoint();

        $selectedEndpoint = $this->endpoints()
            ->first(fn (Endpoint $endpoint): bool => $endpoint->id === $this->selectedEndpointId);

        return [
            ...$this->openApiData(),
            'selectedEndpoint' => $selectedEndpoint,
            'selectedEndpointGroup' => $selectedEndpoint?->group(),
        ];
    }

    /**
     * @return array{
     *     info: array<string, mixed>,
     *     servers: array<int, string>,
     *     endpoints: array<string, array<int, Endpoint>>,
     *     endpointCount: int,
     *     components: array<string, mixed>
     * }
     */
    private function openApiData(): array
    {
        return $this->openApiData ??= app(OpenApiParser::class)->parse(
            app(SpecProvider::class)->spec(),
        );
    }

    private function ensureSelectedEndpoint(): void
    {
        if (
            filled($this->selectedEndpointId)
            && $this->endpoints()->contains(fn (Endpoint $endpoint): bool => $endpoint->id === $this->selectedEndpointId)
        ) {
            return;
        }

        $this->selectedEndpointId = $this->endpoints()->first()?->id;
    }

    /**
     * @return Collection<int, Endpoint>
     */
    private function endpoints(): Collection
    {
        return collect($this->openApiData()['endpoints'])
            ->flatMap(fn (array $groupEndpoints): array => $groupEndpoints)
            ->values();
    }
}

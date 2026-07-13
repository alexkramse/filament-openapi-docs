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

    #[Url(as: 'endpoint', history: true)]
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

    public function getTitle(): string|Htmlable
    {
        return $this->stringValue(config('filament-openapi-docs.page.title'))
            ?? $this->stringValue($this->openApiInfo()['title'] ?? null)
            ?? $this->stringValue(config('app.name'))
            ?? 'Laravel';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->stringValue(config('filament-openapi-docs.page.description'))
            ?? $this->stringValue($this->openApiInfo()['description'] ?? null)
            ?? '';
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

    public static function getNavigationBadge(): ?string
    {
        $mode = config('filament-openapi-docs.navigation.badge', 'version');

        $openApiData = static::staticOpenApiData();
        $badge = match ($mode) {
            'version' => static::staticStringValue($openApiData['info']['version'] ?? null),
            'count' => (string) $openApiData['endpointCount'],
            default => null,
        };

        if ($badge === null) {
            return null;
        }

        return sprintf(
            '%s%s%s',
            (string) config('filament-openapi-docs.navigation.badge_prefix', ''),
            $badge,
            (string) config('filament-openapi-docs.navigation.badge_suffix', ''),
        );
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return match (config('filament-openapi-docs.sub_navigation.position', 'left')) {
            'right' => SubNavigationPosition::End,
            default => SubNavigationPosition::Start,
        };
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

    /**
     * @return array<string, mixed>
     */
    private function openApiInfo(): array
    {
        return $this->openApiData()['info'];
    }

    private function stringValue(mixed $value): ?string
    {
        return static::staticStringValue($value);
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
    private static function staticOpenApiData(): array
    {
        return app(OpenApiParser::class)->parse(
            app(SpecProvider::class)->spec(),
        );
    }

    private static function staticStringValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return (string) $value;
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

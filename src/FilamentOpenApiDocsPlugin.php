<?php

namespace Alexkramse\FilamentOpenapiDocs;

use Alexkramse\FilamentOpenapiDocs\Pages\OpenApiDocsPage;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

class FilamentOpenApiDocsPlugin implements Plugin
{
    public const string ID = 'filament-openapi-docs';

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public static function current(): ?self
    {
        try {
            $plugin = Filament::getPlugin(self::ID);
        } catch (\Throwable) {
            return null;
        }

        return $plugin instanceof self ? $plugin : null;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        if (! $this->shouldRegisterPage()) {
            return;
        }

        $page = OpenApiDocsPage::class;

        if (filled($slug = $this->getSlug())) {
            $page = OpenApiDocsPage::make()->slug($slug);
        }

        $panel->pages([
            $page,
        ]);
    }

    public function boot(Panel $panel): void {}

    public function slug(?string $slug): static
    {
        $this->options['slug'] = $slug;

        return $this;
    }

    public function navigationLabel(?string $label): static
    {
        $this->options['navigation.label'] = $label;

        return $this;
    }

    public function navigationIcon(string|\BackedEnum|Htmlable|null $icon): static
    {
        $this->options['navigation.icon'] = $icon;

        return $this;
    }

    public function navigationGroup(string|\UnitEnum|null $group): static
    {
        $this->options['navigation.group'] = $group;

        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->options['navigation.sort'] = $sort;

        return $this;
    }

    public function navigationBadge(?string $badge): static
    {
        $this->options['navigation.badge'] = $badge;

        return $this;
    }

    public function navigationBadgePrefix(?string $prefix): static
    {
        $this->options['navigation.badge_prefix'] = $prefix;

        return $this;
    }

    public function navigationBadgeSuffix(?string $suffix): static
    {
        $this->options['navigation.badge_suffix'] = $suffix;

        return $this;
    }

    public function subNavigationPosition(string $position): static
    {
        $this->options['sub_navigation.position'] = $position;

        return $this;
    }

    public function title(?string $title): static
    {
        $this->options['page.title'] = $title;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->options['page.description'] = $description;

        return $this;
    }

    public function enabledInProduction(bool $condition = true): static
    {
        $this->options['page.enabled_in_production'] = $condition;

        return $this;
    }

    public function fullWidth(bool $condition = true): static
    {
        $this->options['layout.full_width'] = $condition;

        return $this;
    }

    public function requestSamples(bool $condition = true): static
    {
        $this->options['request_samples.enabled'] = $condition;

        return $this;
    }

    public function developerOptions(bool $condition = true): static
    {
        $this->options['request_samples.developer_options'] = $condition;

        return $this;
    }

    public function defaultServer(?string $server): static
    {
        $this->options['request_samples.default_server'] = $server;

        return $this;
    }

    public function scrambleGenerator(string $generator): static
    {
        $this->options['scramble.generator'] = $generator;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->option('slug', 'slug');
    }

    public function getNavigationLabel(): ?string
    {
        return $this->option('navigation.label', 'navigation.label', 'API Docs');
    }

    public function getNavigationIcon(): string|\BackedEnum|Htmlable|null
    {
        return $this->option('navigation.icon', 'navigation.icon', 'heroicon-o-document-text');
    }

    public function getNavigationGroup(): string|\UnitEnum|null
    {
        return $this->option('navigation.group', 'navigation.group');
    }

    public function getNavigationSort(): ?int
    {
        $sort = $this->option('navigation.sort', 'navigation.sort', 100);

        return $sort === null ? null : (int) $sort;
    }

    public function getNavigationBadgeMode(): ?string
    {
        return $this->option('navigation.badge', 'navigation.badge', 'version');
    }

    public function getNavigationBadgePrefix(): string
    {
        return (string) $this->option('navigation.badge_prefix', 'navigation.badge_prefix', '');
    }

    public function getNavigationBadgeSuffix(): string
    {
        return (string) $this->option('navigation.badge_suffix', 'navigation.badge_suffix', '');
    }

    public function getSubNavigationPosition(): string
    {
        return (string) $this->option('sub_navigation.position', 'sub_navigation.position', 'left');
    }

    public function getTitle(): ?string
    {
        return $this->option('page.title', 'page.title');
    }

    public function getDescription(): ?string
    {
        return $this->option('page.description', 'page.description');
    }

    public function isEnabledInProduction(): bool
    {
        return (bool) $this->option('page.enabled_in_production', 'page.enabled_in_production', false);
    }

    public function hasFullWidthLayout(): bool
    {
        return (bool) $this->option('layout.full_width', 'layout.full_width', true);
    }

    public function hasRequestSamples(): bool
    {
        return (bool) $this->option('request_samples.enabled', 'request_samples.enabled', true);
    }

    public function hasDeveloperOptions(): bool
    {
        return (bool) $this->option('request_samples.developer_options', 'request_samples.developer_options', false);
    }

    public function getDefaultServer(): ?string
    {
        return $this->option('request_samples.default_server', 'request_samples.default_server');
    }

    public function getScrambleGenerator(): string
    {
        return (string) $this->option('scramble.generator', 'scramble.generator', 'default');
    }

    private function shouldRegisterPage(): bool
    {
        return ! app()->environment('production') || $this->isEnabledInProduction();
    }

    private function option(string $option, string $config, mixed $default = null): mixed
    {
        if (array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }

        return config("filament-openapi-docs.{$config}", $default);
    }
}

<?php

namespace Kramarenko\FilamentOpenApiDocs;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Kramarenko\FilamentOpenApiDocs\Pages\OpenApiDocsPage;

class FilamentOpenApiDocsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-openapi-docs';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            OpenApiDocsPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
    }
}

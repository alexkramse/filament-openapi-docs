<?php

namespace Alexkramse\FilamentOpenapiDocs;

use Alexkramse\FilamentOpenapiDocs\Pages\OpenApiDocsPage;
use Filament\Contracts\Plugin;
use Filament\Panel;

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

    public function boot(Panel $panel): void {}
}

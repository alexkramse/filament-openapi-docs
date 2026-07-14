## Filament OpenAPI Docs

**Native Filament OpenAPI explorer driven by Scramble's generated specification**

### Installation

Install the package and publish Filament assets:

```bash
composer require alexkramse/filament-openapi-docs
php artisan filament:assets
```

The package expects `dedoc/scramble` to be installed and configured, because the default spec provider renders Scramble's generated OpenAPI document.

### Panel registration

Register the plugin in your Filament panel provider:

```php
use Alexkramse\FilamentOpenapiDocs\FilamentOpenApiDocsPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            FilamentOpenApiDocsPlugin::make()
        );
}
```

The default page slug is `api-docs`, so an admin panel at `/admin` exposes the page at `/admin/api-docs`.

### Configuration

You may publish the config file:

```bash
php artisan vendor:publish --tag=filament-openapi-docs-config
```

Panel-level fluent configuration overrides the config file:

```php
FilamentOpenApiDocsPlugin::make()
    ->slug('developer/api-docs')
    ->navigationLabel('OpenAPI')
    ->navigationGroup('Developer')
    ->navigationBadge('count')
    ->navigationBadgePrefix('')
    ->navigationBadgeSuffix(' endpoints')
    ->subNavigationPosition('right')
    ->fullWidth()
    ->requestSamples()
    ->defaultServer('https://api.example.com')
    ->scrambleGenerator('default');
```

Supported navigation badge modes are `version`, `count`, and `null`.

### Assets

The package follows Filament's asset manager conventions:

- CSS is registered with `loadedOnRequest()` and lazy-loaded only on the OpenAPI docs page.
- The request snippet UI is registered as an asynchronous Alpine component.
- Assets are registered under the Composer package name `alexkramse/filament-openapi-docs` to avoid clashes with other plugins.

When developing this package locally, rebuild package assets with:

```bash
npm run build
```

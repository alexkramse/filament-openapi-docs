# Filament OpenAPI Docs

Native OpenAPI documentation for Filament panels, powered by Scramble.

Filament OpenAPI Docs adds a dashboard page to your Filament panel where authenticated dashboard users can browse your generated OpenAPI specification, inspect endpoints, view request examples, and test API calls without leaving the admin area.

## Features

- Adds a native Filament page inside your existing panel navigation.
- Reads the generated OpenAPI document from Scramble by default.
- Groups endpoints in Filament sub-navigation for fast browsing.
- Shows endpoint methods, paths, parameters, request bodies, responses, schemas, and examples.
- Generates request samples for multiple languages and clients.
- Lets users test API endpoints directly from the dashboard.
- Provides developer mode for custom request headers, query parameters, path parameters, auth values, media headers, and request bodies.
- Supports panel-level fluent configuration and a publishable config file.
- Registers package CSS and JavaScript through Filament's asset manager.

## Requirements

- PHP `^8.3`
- Laravel `^13.0`
- Filament `^5.0`
- Scramble `^0.13.30`

Scramble is installed as a package dependency, but your Laravel application still needs a working Scramble configuration so the OpenAPI document can be generated correctly.

## Installation

Install the package with Composer:

```bash
composer require alexkramse/filament-openapi-docs
```

Publish Filament assets so the package CSS and async Alpine component are available in the browser:

```bash
php artisan filament:assets
```

You should also run `php artisan filament:assets` after package updates and during deployment if your application does not already run Filament's asset upgrade command automatically.

## Register The Plugin

Register the plugin in the Filament panel where the API documentation should appear:

```php
<?php

namespace App\Providers\Filament;

use Alexkramse\FilamentOpenapiDocs\FilamentOpenApiDocsPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(FilamentOpenApiDocsPlugin::make());
    }
}
```

The default page slug is `api-docs`. For example, if your panel is available at `/admin`, the documentation page will be available at `/admin/api-docs`.

Access is handled by your Filament panel. Users must pass the same authentication, middleware, and authorization rules that protect the panel.

## Usage

After registration, open your Filament dashboard and select the API Docs navigation item.

The page displays your OpenAPI document as a Filament-native interface:

- the page title and description come from plugin configuration, config, or OpenAPI info;
- endpoint groups are shown in the page sub-navigation;
- selecting an endpoint updates the URL query string so links can be shared inside the team;
- request parameters, body examples, response schemas, and examples are rendered from the OpenAPI specification.

When request samples are enabled, each endpoint includes generated request snippets and a request tester. The tester sends requests from the browser, so the selected server URL, CORS rules, cookies, and API authentication must allow the request.

Developer mode is available inside the request tester. It allows trusted dashboard users to add or edit runtime request values before sending:

- custom headers;
- path parameter values;
- query parameters;
- auth header or query values;
- content negotiation headers;
- JSON or raw request body content.

## Configuration

You may publish the config file:

```bash
php artisan vendor:publish --tag=filament-openapi-docs-config
```

Panel-level fluent configuration overrides the config file:

```php
use Alexkramse\FilamentOpenapiDocs\FilamentOpenApiDocsPlugin;

FilamentOpenApiDocsPlugin::make()
    ->slug('developer/api-docs')
    ->navigationLabel('OpenAPI')
    ->navigationIcon('heroicon-o-document-text')
    ->navigationGroup('Developer')
    ->navigationSort(100)
    ->navigationBadge('count')
    ->navigationBadgePrefix('')
    ->navigationBadgeSuffix(' endpoints')
    ->subNavigationPosition('right')
    ->title('API Documentation')
    ->description('Browse and test available API endpoints.')
    ->fullWidth()
    ->requestSamples()
    ->defaultServer('https://api.example.com')
    ->scrambleGenerator('default');
```

### Available Options

| Method                    | Config key                       | Description                                       |
| ------------------------- | -------------------------------- | ------------------------------------------------- |
| `slug()`                  | `slug`                           | Changes the Filament page slug.                   |
| `navigationLabel()`       | `navigation.label`               | Changes the navigation label.                     |
| `navigationIcon()`        | `navigation.icon`                | Changes the navigation icon.                      |
| `navigationGroup()`       | `navigation.group`               | Places the page in a navigation group.            |
| `navigationSort()`        | `navigation.sort`                | Controls navigation ordering.                     |
| `navigationBadge()`       | `navigation.badge`               | Shows `version`, `count`, or no badge with `null`. |
| `navigationBadgePrefix()` | `navigation.badge_prefix`        | Adds text before the badge value.                 |
| `navigationBadgeSuffix()` | `navigation.badge_suffix`        | Adds text after the badge value.                  |
| `subNavigationPosition()` | `sub_navigation.position`        | Uses `left` or `right` endpoint navigation.        |
| `title()`                 | `page.title`                     | Overrides the page title.                         |
| `description()`           | `page.description`               | Overrides the page description.                   |
| `fullWidth()`             | `layout.full_width`              | Uses Filament's full-width page layout.           |
| `requestSamples()`        | `request_samples.enabled`        | Enables or disables snippets and request testing. |
| `defaultServer()`         | `request_samples.default_server` | Sets the default server for generated requests.   |
| `scrambleGenerator()`     | `scramble.generator`             | Selects the Scramble generator name.              |

Supported navigation badge modes are `version`, `count`, and `null`.

## Custom Spec Providers

Scramble is the default OpenAPI source. If you need to load a specification from another source, bind a custom provider through the config file.

Your provider must implement `Alexkramse\FilamentOpenapiDocs\Support\SpecProvider`:

```php
use Alexkramse\FilamentOpenapiDocs\Support\SpecProvider;
use Dedoc\Scramble\GeneratorConfig;

class CustomSpecProvider implements SpecProvider
{
    public function config(): GeneratorConfig
    {
        // Return the generator config used by the docs renderer.
    }

    public function view(): string
    {
        // Return the renderer view name used by the spec provider.
    }

    public function spec(): array
    {
        // Return the OpenAPI document as an array.
    }
}
```

Then update `config/filament-openapi-docs.php`:

```php
'provider' => CustomSpecProvider::class,
```

## Assets

The package follows Filament's asset manager conventions:

- CSS is registered with `loadedOnRequest()` and is only loaded when requested by the OpenAPI docs page.
- The request tester is registered as an asynchronous Alpine component.
- Assets are registered under the package name `alexkramse/filament-openapi-docs`.

For application installation and deployment, run:

```bash
php artisan filament:assets
```

For local package development, rebuild package assets after changing files in `resources/js` or `resources/css`:

```bash
npm install
npm run build
```

You do not need to run `npm run build` in a consuming Laravel application unless you are developing this package locally or changing its frontend source files.

## Security Notes

This package is intended for trusted dashboard users. The request tester can send custom headers, parameters, auth values, and request bodies from the browser.

Before enabling request testing in production, confirm that:

- the Filament panel is protected by the right authentication and authorization rules;
- the configured API server is safe for dashboard users to call;
- CORS, cookies, CSRF behavior, and API authentication are configured intentionally;
- sensitive production endpoints should be exposed only to users who are allowed to test them.

You can disable request samples and request testing while keeping the documentation page:

```php
FilamentOpenApiDocsPlugin::make()
    ->requestSamples(false);
```

## Development

Install PHP and JavaScript dependencies:

```bash
composer install
npm install
```

Build frontend assets:

```bash
npm run build
```

Run the test suite:

```bash
vendor/bin/pest --compact
```

Format PHP files:

```bash
vendor/bin/pint --dirty --format agent
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

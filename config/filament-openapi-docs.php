<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAPI Specification Provider
    |--------------------------------------------------------------------------
    |
    | Selects the source used to load the OpenAPI specification rendered by
    | the Filament page. The built-in provider is "scramble".
    |
    */
    'provider' => 'scramble',

    'scramble' => [
        /*
        |--------------------------------------------------------------------------
        | Scramble Generator
        |--------------------------------------------------------------------------
        |
        | The Scramble generator key used to build the OpenAPI specification.
        | Keep "default" unless your application registers multiple generators.
        |
        */
        'generator' => 'default',
    ],

    'navigation' => [
        /*
        |--------------------------------------------------------------------------
        | Navigation Label
        |--------------------------------------------------------------------------
        |
        | The label shown for the OpenAPI documentation page in Filament's
        | sidebar navigation.
        |
        */
        'label' => 'API Docs',

        /*
        |--------------------------------------------------------------------------
        | Navigation Icon
        |--------------------------------------------------------------------------
        |
        | The Filament icon shown beside the navigation label. Use any icon name
        | supported by your Filament installation.
        |
        */
        'icon' => 'heroicon-o-document-text',

        /*
        |--------------------------------------------------------------------------
        | Navigation Group
        |--------------------------------------------------------------------------
        |
        | The sidebar group that contains the documentation page. Set this to
        | null to render the page as an ungrouped navigation item.
        |
        */
        'group' => 'Developer',

        /*
        |--------------------------------------------------------------------------
        | Navigation Sort
        |--------------------------------------------------------------------------
        |
        | Controls where the documentation page appears within its navigation
        | group. Lower values are displayed earlier.
        |
        */
        'sort' => 100,

        /*
        |--------------------------------------------------------------------------
        | Navigation Badge
        |--------------------------------------------------------------------------
        |
        | Controls the badge displayed beside the navigation label. Supported
        | values are "version", "count", and null. Unknown values render no badge.
        |
        */
        'badge' => 'version',

        /*
        |--------------------------------------------------------------------------
        | Navigation Badge Prefix
        |--------------------------------------------------------------------------
        |
        | Optional text prepended to the resolved badge value. It is only applied
        | when a badge value is available.
        |
        */
        'badge_prefix' => '',

        /*
        |--------------------------------------------------------------------------
        | Navigation Badge Suffix
        |--------------------------------------------------------------------------
        |
        | Optional text appended to the resolved badge value. It is only applied
        | when a badge value is available.
        |
        */
        'badge_suffix' => '',
    ],

    'sub_navigation' => [
        /*
        |--------------------------------------------------------------------------
        | Sub-Navigation Position
        |--------------------------------------------------------------------------
        |
        | Controls where endpoint sub-navigation is rendered. Supported values
        | are "left" and "right".
        |
        */
        'position' => 'left',
    ],

    'page' => [
        /*
        |--------------------------------------------------------------------------
        | Page Title
        |--------------------------------------------------------------------------
        |
        | Overrides the page title. Leave empty to fall back to OpenAPI
        | info.title, then the application name.
        |
        */
        'title' => 'API Docs',

        /*
        |--------------------------------------------------------------------------
        | Page Description
        |--------------------------------------------------------------------------
        |
        | Overrides the page subheading. Leave empty to fall back to OpenAPI
        | info.description.
        |
        */
        'description' => '',
    ],

    'layout' => [
        /*
        |--------------------------------------------------------------------------
        | Full Width Layout
        |--------------------------------------------------------------------------
        |
        | When enabled, the OpenAPI page uses Filament's full-width content area.
        | Disable this to use the panel's default page width.
        |
        */
        'full_width' => true,
    ],

    'request_samples' => [
        /*
        |--------------------------------------------------------------------------
        | Request Samples
        |--------------------------------------------------------------------------
        |
        | Enables interactive request sample generation and the request sender
        | controls in endpoint documentation.
        |
        */
        'enabled' => true,

        /*
        |--------------------------------------------------------------------------
        | Default Server
        |--------------------------------------------------------------------------
        |
        | Optional server URL used when the OpenAPI document does not define one
        | or when you want request samples to prefer a specific API server.
        |
        */
        'default_server' => null,
    ],
];

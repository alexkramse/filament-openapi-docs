<?php

return [
    'provider' => 'scramble',

    'scramble' => [
        'generator' => 'default',
        'reuse_view' => true,
    ],

    'navigation' => [
        'label' => 'API Docs',
        'icon' => 'heroicon-o-document-text',
        'group' => 'Developer',
        'sort' => 100,
    ],

    'layout' => [
        'full_width' => true,
    ],

    'request_samples' => [
        'enabled' => true,
        'default_server' => null,
    ],
];

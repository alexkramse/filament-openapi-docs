<?php

use Alexkramse\FilamentOpenapiDocs\Services\SchemaPresenter;

it('renders rows for composed request body schemas', function () {
    $schema = demoOrderSchema();
    $components = demoOrderComponents();
    $rows = app(SchemaPresenter::class)->rows($schema, $components);

    expect($rows)->toHaveCount(4);

    $indexedRows = collect($rows)->keyBy('name');

    expect($indexedRows->keys()->all())->toBe([
        'customer',
        'items',
        'priority',
        'coupon_code',
    ])
        ->and($indexedRows->get('customer')['required'])->toBeTrue()
        ->and($indexedRows->get('items')['required'])->toBeTrue()
        ->and($indexedRows->get('priority')['required'])->toBeTrue()
        ->and($indexedRows->get('coupon_code')['required'])->toBeFalse()
        ->and(collect($indexedRows->get('customer')['children'])->pluck('name')->all())->toBe([
            'name',
            'email',
        ])
        ->and(collect($indexedRows->get('items')['children'][0]['children'])->pluck('name')->all())->toBe([
            'sku',
            'quantity',
            'unit_price',
        ]);
});

it('does not show the empty structured fields message for composed schemas', function () {
    $html = view('filament-openapi-docs::components.schema-tree', [
        'schema'     => demoOrderSchema(),
        'components' => demoOrderComponents(),
    ])->render();

    expect($html)->toContain('customer')
        ->and($html)->toContain('priority')
        ->and($html)->toContain('foad-schema-property-main')
        ->and($html)->toContain('foad-schema-type-badges')
        ->and($html)->toContain('foad-schema-presence-badge')
        ->and($html)->not->toContain('No structured fields documented.');
});

/**
 * @return array<string, mixed>
 */
function demoOrderSchema(): array
{
    return [
        'allOf' => [
            ['$ref' => '#/components/schemas/StoreDemoOrderRequest'],
            [
                'type'       => 'object',
                'required'   => ['priority'],
                'properties' => [
                    'priority' => [
                        'type'        => 'string',
                        'description' => 'Shipping priority: standard, express, or overnight.',
                        'example'     => 'express',
                    ],
                    'coupon_code' => [
                        'type'        => ['string', 'null'],
                        'description' => 'Optional coupon code.',
                        'example'     => null,
                    ],
                ],
            ],
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function demoOrderComponents(): array
{
    return [
        'schemas' => [
            'StoreDemoOrderRequest' => [
                'type'       => 'object',
                'required'   => ['customer', 'items'],
                'properties' => [
                    'customer' => [
                        'type'       => 'object',
                        'required'   => ['name', 'email'],
                        'properties' => [
                            'name' => [
                                'type'        => 'string',
                                'description' => 'Customer full name.',
                                'example'     => 'Grace Hopper',
                            ],
                            'email' => [
                                'type'        => 'string',
                                'format'      => 'email',
                                'description' => 'Customer email address.',
                                'example'     => 'grace@example.test',
                            ],
                        ],
                    ],
                    'items' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'required'   => ['sku', 'quantity', 'unit_price'],
                            'properties' => [
                                'sku' => [
                                    'type'        => 'string',
                                    'description' => 'Line item SKU.',
                                    'example'     => 'DEMO-BOOK',
                                ],
                                'quantity' => [
                                    'type'        => 'integer',
                                    'description' => 'Line item quantity.',
                                    'example'     => 2,
                                ],
                                'unit_price' => [
                                    'type'        => 'number',
                                    'format'      => 'float',
                                    'description' => 'Line item unit price.',
                                    'example'     => 19.95,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

@php
    $presenter = app(\Alexkramse\FilamentOpenapiDocs\Services\SchemaPresenter::class);
    $schemaComponents = $components ?? [];
    $rows = $presenter->rows($schema, $schemaComponents);
@endphp

<div>
    @if ($rows !== [])
        <div class="foad-schema-tree">
            @foreach ($rows as $row)
                @include('filament-openapi-docs::components.schema-row', ['row' => $row, 'depth' => 0])
            @endforeach
        </div>
    @elseif (isset($schema['$ref']))
        <div class="foad-property-row">
            <div class="foad-property-main">
                <span class="foad-property-name">{{ $presenter->label($schema, $schemaComponents) }}</span>
                <x-filament::badge color="gray" size="xs">{{ __('filament-openapi-docs::ui.labels.reference') }}</x-filament::badge>
            </div>
        </div>
    @else
        <p class="fi-section-header-description">{{ __('filament-openapi-docs::ui.empty.structured_fields') }}</p>
    @endif
</div>

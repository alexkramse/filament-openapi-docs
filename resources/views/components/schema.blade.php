@php
    $presenter = app(\Kramarenko\FilamentOpenApiDocs\Services\SchemaPresenter::class);
    $rows = $presenter->rows($schema);
@endphp

<x-filament::section heading="Schema" compact secondary>
    <x-slot name="afterHeader">
        <x-filament::badge color="info">
            {{ $presenter->label($schema) }}
        </x-filament::badge>
    </x-slot>

    @if (isset($schema['$ref']))
        <label class="fi-fo-field">
            <span class="fi-fo-field-label-content">Reference</span>

            <x-filament::input.wrapper prefix-icon="heroicon-o-cube">
                <x-filament::input
                    :value="$presenter->label($schema)"
                    readonly
                />
            </x-filament::input.wrapper>
        </label>
    @elseif ($rows !== [])
        <div class="foad-stack foad-stack-sm">
            @foreach ($rows as $row)
                @include('filament-openapi-docs::components.schema-row', ['row' => $row, 'depth' => 0])
            @endforeach
        </div>
    @else
        <p class="fi-section-header-description">No structured fields documented.</p>
    @endif
</x-filament::section>

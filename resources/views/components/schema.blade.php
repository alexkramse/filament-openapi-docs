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
        <label class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-950 dark:text-white">Reference</span>

            <x-filament::input.wrapper prefix-icon="heroicon-o-cube">
                <x-filament::input
                    :value="$presenter->label($schema)"
                    readonly
                    class="font-mono text-xs"
                />
            </x-filament::input.wrapper>
        </label>
    @elseif ($rows !== [])
        <div class="flex flex-col gap-2">
            @foreach ($rows as $row)
                @include('filament-openapi-docs::components.schema-row', ['row' => $row, 'depth' => 0])
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">No structured fields documented.</p>
    @endif
</x-filament::section>

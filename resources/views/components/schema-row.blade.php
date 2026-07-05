@php
    $hasChildren = $row['children'] !== [];
@endphp

<{{ $hasChildren ? 'details' : 'div' }}
    class="foad-property-row {{ $hasChildren ? 'foad-property-row-collapsible' : '' }}"
    style="--foad-depth: {{ $depth }};"
>
    @if ($hasChildren)
        <summary class="foad-property-summary">
    @endif
    <div class="foad-property-main">
        <span class="foad-property-title">
            <span class="foad-property-connector" aria-hidden="true"></span>

            @if ($hasChildren)
                <span class="foad-property-toggle" aria-hidden="true">
                    @svg('heroicon-m-chevron-down', 'foad-property-toggle-icon foad-property-toggle-icon-collapsed')
                    @svg('heroicon-m-chevron-up', 'foad-property-toggle-icon foad-property-toggle-icon-open')
                </span>
            @endif

            <span class="foad-property-name">{{ $row['name'] }}</span>
        </span>

        <div class="foad-inline-list foad-inline-list-sm">
            @foreach ($row['types'] as $type)
                <x-filament::badge color="info" size="xs">{{ $type }}</x-filament::badge>
            @endforeach

            <x-filament::badge :color="$row['required'] ? 'danger' : 'gray'" size="xs">
                {{ $row['required'] ? 'Required' : 'Optional' }}
            </x-filament::badge>
        </div>
    </div>

    @if (filled($row['description']))
        <p class="foad-property-description">{{ $row['description'] }}</p>
    @endif

    @if ($row['enum'] !== [])
        <div class="foad-property-meta">
            <span class="foad-property-meta-label">Allowed</span>
            <div class="foad-inline-list foad-inline-list-sm">
                @foreach ($row['enum'] as $enum)
                    <x-filament::badge color="gray" size="xs">{{ $enum }}</x-filament::badge>
                @endforeach
            </div>
        </div>
    @endif

    @if ($row['examples'] !== [])
        <div class="foad-property-meta">
            <span class="foad-property-meta-label">Example</span>
            <div class="foad-inline-list foad-inline-list-sm">
                @foreach ($row['examples'] as $example)
                    <x-filament::badge color="gray" size="xs">{{ $example }}</x-filament::badge>
                @endforeach
            </div>
        </div>
    @endif
    @if ($hasChildren)
        </summary>
    @endif

    @if ($hasChildren)
        <div class="foad-schema-tree">
            @foreach ($row['children'] as $child)
                @include('filament-openapi-docs::components.schema-row', ['row' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</{{ $hasChildren ? 'details' : 'div' }}>

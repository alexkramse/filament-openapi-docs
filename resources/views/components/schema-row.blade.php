<div class="foad-property-row" style="--foad-depth: {{ $depth }};">
    <div class="foad-property-main">
        <span class="foad-property-name">{{ $row['name'] }}</span>

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

    @if ($row['children'] !== [])
        <div class="foad-schema-tree">
            @foreach ($row['children'] as $child)
                @include('filament-openapi-docs::components.schema-row', ['row' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>

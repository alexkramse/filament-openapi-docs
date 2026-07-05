<div @class([
    'fi-section fi-secondary fi-compact',
    'foad-nested-schema-row' => $depth > 0,
])>
    <div class="fi-section-content-ctn">
        <div class="fi-section-content foad-stack foad-stack-md">
            <div class="foad-schema-row-header">
                <label class="fi-fo-field">
                    <span class="fi-fo-field-label-content">Field</span>

                    <x-filament::input.wrapper prefix-icon="heroicon-o-variable">
                        <x-filament::input
                            :value="$row['name']"
                            readonly
                        />
                    </x-filament::input.wrapper>
                </label>

                <div class="foad-inline-list foad-inline-list-end">
                    <x-filament::badge color="info">{{ $row['type'] }}</x-filament::badge>

                    @if ($row['required'])
                        <x-filament::badge color="danger">Required</x-filament::badge>
                    @else
                        <x-filament::badge color="gray">Optional</x-filament::badge>
                    @endif
                </div>
            </div>

            @if (filled($row['description']))
                <label class="fi-fo-field">
                    <span class="fi-fo-field-label-content">Description</span>

                    <x-filament::input.wrapper>
                        <x-filament::input
                            :value="$row['description']"
                            readonly
                        />
                    </x-filament::input.wrapper>
                </label>
            @endif

            @if ($row['enum'] !== [])
                <div class="fi-fo-field">
                    <span class="fi-fo-field-label-content">Allowed values</span>

                    <div class="foad-inline-list foad-inline-list-sm">
                        @foreach ($row['enum'] as $enum)
                            <x-filament::badge color="gray">{{ $enum }}</x-filament::badge>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($row['children'] !== [])
                <x-filament::section heading="Nested fields" collapsible collapsed compact secondary>
                    <div class="foad-stack foad-stack-sm">
                        @foreach ($row['children'] as $child)
                            @include('filament-openapi-docs::components.schema-row', ['row' => $child, 'depth' => $depth + 1])
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</div>

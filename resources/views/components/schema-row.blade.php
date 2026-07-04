<div @class([
    'rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900',
    'ml-4' => $depth > 0,
])>
    <div class="flex flex-col gap-3">
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
            <label class="flex min-w-0 flex-col gap-1">
                <span class="text-sm font-medium text-gray-950 dark:text-white">Field</span>

                <x-filament::input.wrapper prefix-icon="heroicon-o-variable">
                    <x-filament::input
                        :value="$row['name']"
                        readonly
                        class="font-mono text-sm"
                    />
                </x-filament::input.wrapper>
            </label>

            <div class="flex flex-wrap items-end gap-2 md:justify-end">
                <x-filament::badge color="info">{{ $row['type'] }}</x-filament::badge>

                @if ($row['required'])
                    <x-filament::badge color="danger">Required</x-filament::badge>
                @else
                    <x-filament::badge color="gray">Optional</x-filament::badge>
                @endif
            </div>
        </div>

        @if (filled($row['description']))
            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium text-gray-950 dark:text-white">Description</span>

                <x-filament::input.wrapper>
                    <x-filament::input
                        :value="$row['description']"
                        readonly
                    />
                </x-filament::input.wrapper>
            </label>
        @endif

        @if ($row['enum'] !== [])
            <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-gray-950 dark:text-white">Allowed values</span>

                <div class="flex flex-wrap gap-1">
                    @foreach ($row['enum'] as $enum)
                        <x-filament::badge color="gray">{{ $enum }}</x-filament::badge>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($row['children'] !== [])
            <x-filament::section heading="Nested fields" collapsible collapsed compact secondary>
                <div class="flex flex-col gap-2">
                    @foreach ($row['children'] as $child)
                        @include('filament-openapi-docs::components.schema-row', ['row' => $child, 'depth' => $depth + 1])
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>
</div>

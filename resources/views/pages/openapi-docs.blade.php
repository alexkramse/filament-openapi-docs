<x-filament-panels::page>
    <div
        x-data="{}"
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('openapi-docs', package: 'alexkramse/filament-openapi-docs'))]"
        class="foad-stack"
    >
        <x-filament::section
            :heading="$info['title'] ?? 'API Documentation'"
            :description="$info['description'] ?? null"
        >
            <x-slot name="heading">
                @if ($servers !== [])
                    @foreach ($servers as $server)
                        <x-filament::badge color="gray">
                            {{ $server }}
                        </x-filament::badge>
                    @endforeach
                @endif

                @if (filled($info['version'] ?? null))
                    <x-filament::badge color="gray">
                        v{{ $info['version'] }}
                    </x-filament::badge>
                @endif
                <x-filament::badge color="primary">
                    {{ $endpointCount }} endpoints
                </x-filament::badge>
                <x-filament::badge color="gray">
                    {{ count($endpoints) }} groups
                </x-filament::badge>
            </x-slot>

        </x-filament::section>

        @if ($selectedEndpoint)
            <section class="foad-stack">
                @include('filament-openapi-docs::components.endpoint', ['endpoint' => $selectedEndpoint])
            </section>
        @endif
    </div>
</x-filament-panels::page>

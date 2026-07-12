<x-filament-panels::page>
    <div
        x-data="{
            init() {
                this.$nextTick(() => {
                    const labels = Array.from(document.querySelectorAll('.fi-page-sub-navigation-sidebar [data-group-label]'))
                        .map((group) => group.dataset.groupLabel)
                        .filter(Boolean)

                    if (! labels.length || ! this.$store.sidebar?.collapsedGroups) {
                        return
                    }

                    this.$store.sidebar.collapsedGroups = [
                        ...this.$store.sidebar.collapsedGroups.filter((label) => ! labels.includes(label)),
                        ...labels.slice(1),
                    ]
                })
            },
        }"
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
                        <x-filament::badge color="info">
                            {{ $server }}
                        </x-filament::badge>
                    @endforeach
                @endif

                @if (filled($info['version'] ?? null))
                    <x-filament::badge color="primary">
                        v{{ $info['version'] }}
                    </x-filament::badge>
                @endif
                <x-filament::badge color="gray">
                    {{ $endpointCount }} endpoints
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

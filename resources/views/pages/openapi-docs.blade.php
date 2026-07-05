<x-filament-panels::page
    x-data="{ activeTab: 'YourFirstTabLabel' }">
    <style>
        .foad-openapi-docs-page .fi-page-sub-navigation-sidebar-ctn {
            position: sticky;
            top: 1.5rem;
            max-height: calc(100vh - 3rem);
            overflow-y: auto;
        }

        .foad-stack {
            display: grid;
            gap: 1.5rem;
        }

        .foad-stack-md {
            gap: 1rem;
        }

        .foad-stack-sm {
            gap: .5rem;
        }

        .foad-grid {
            display: grid;
            gap: .75rem;
        }

        .foad-inline-list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }

        .foad-inline-list-sm {
            gap: .25rem;
        }

        .foad-inline-list-end {
            align-items: flex-end;
        }

        .foad-schema-row-header {
            display: grid;
            gap: .75rem;
        }

        .foad-nested-schema-row {
            margin-inline-start: 1rem;
        }

        @media (min-width: 48rem) {
            .foad-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .foad-schema-row-header {
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: start;
            }

            .foad-inline-list-end {
                justify-content: flex-end;
            }
        }
    </style>
    <div class="foad-stack">
        <x-filament::section
            :heading="$info['title'] ?? 'API Documentation'"
            :description="$info['description'] ?? null"
        >
            <x-slot name="heading">
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

            @if ($servers !== [])
                <div class="foad-grid">
                    @foreach ($servers as $server)
                        <label class="fi-fo-field">
                            <x-filament::section.description prefix-icon="heroicon-o-server">
                                {{ $server }}
                            </x-filament::section.description>

                            {{--                                <x-filament::input :value="$server" readonly />--}}
                        </label>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        @if ($selectedEndpoint)
            <section class="foad-stack">
                @include('filament-openapi-docs::components.endpoint', ['endpoint' => $selectedEndpoint])
            </section>
        @endif
    </div>
</x-filament-panels::page>

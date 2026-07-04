<x-filament-panels::page>
    <style>
        .foad-docs-shell {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .foad-endpoint-sidebar {
            flex: 0 0 auto;
            width: 100%;
        }

        .foad-endpoint-sidebar.foad-is-collapsed {
            width: 100%;
        }

        .foad-docs-content {
            min-width: 0;
            width: 100%;
            flex: 1 1 0%;
        }

        .foad-endpoint-navigation {
            width: 100%;
        }

        .foad-endpoint-navigation .fi-page-sub-navigation-sidebar {
            width: 100%;
        }

        @media (min-width: 1024px) {
            .foad-docs-shell {
                align-items: flex-start;
                flex-direction: row;
            }

            .foad-endpoint-sidebar {
                position: sticky;
                top: 1.5rem;
                width: 20rem;
            }

            .foad-endpoint-sidebar.foad-is-collapsed {
                width: 4rem;
            }
        }
    </style>

    <div
        x-data="{ navigationCollapsed: false }"
        class="foad-docs-shell"
    >
        <aside
            x-bind:class="{ 'foad-is-collapsed': navigationCollapsed }"
            class="foad-endpoint-sidebar"
        >
            <x-filament::section compact>
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-2">
                        <div x-show="! navigationCollapsed" class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Endpoints</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Fast navigation</p>
                        </div>

                        <x-filament::icon-button
                            color="gray"
                            icon="heroicon-o-bars-3"
                            label="Toggle endpoint navigation"
                            x-on:click="navigationCollapsed = ! navigationCollapsed"
                        />
                    </div>

                    <div x-show="! navigationCollapsed" x-cloak>
                        @include('filament-openapi-docs::components.endpoint-navigation', ['navigation' => $endpointNavigation])
                    </div>

                    <div x-show="navigationCollapsed" x-cloak class="flex flex-col items-center gap-2">
                        @foreach ($endpoints as $group => $groupEndpoints)
                            <a
                                href="#{{ $groupEndpoints[0]->id }}"
                                class="flex h-9 w-9 items-center justify-center rounded-md text-xs font-semibold text-gray-600 ring-1 ring-gray-200 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/5 dark:hover:text-white"
                                title="{{ $group }}"
                            >
                                {{ str($group)->substr(0, 1)->upper() }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </x-filament::section>
        </aside>

        <main class="foad-docs-content flex flex-col gap-6">
            <x-filament::section
                :heading="$info['title'] ?? 'API Documentation'"
                :description="$info['description'] ?? null"
                icon="heroicon-o-document-text"
            >
                <x-slot name="afterHeader">
                    <div class="flex flex-wrap items-center gap-2">
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
                    </div>
                </x-slot>

                @if ($servers !== [])
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($servers as $server)
                            <label class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-gray-950 dark:text-white">Server</span>

                                <x-filament::input.wrapper prefix-icon="heroicon-o-server">
                                    <x-filament::input
                                        :value="$server"
                                        readonly
                                        class="font-mono text-xs"
                                    />
                                </x-filament::input.wrapper>
                            </label>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>

            @foreach ($endpoints as $group => $groupEndpoints)
                <section class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $group }}</h3>
                        <x-filament::badge color="gray" size="xs">{{ count($groupEndpoints) }}</x-filament::badge>
                    </div>

                    <div class="flex flex-col gap-4">
                        @foreach ($groupEndpoints as $endpoint)
                            @include('filament-openapi-docs::components.endpoint', ['endpoint' => $endpoint])
                        @endforeach
                    </div>
                </section>
            @endforeach
        </main>
    </div>
</x-filament-panels::page>

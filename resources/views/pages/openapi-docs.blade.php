<x-filament-panels::page>
    <div class="flex flex-col gap-6">
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

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="xl:sticky xl:top-6 xl:self-start">
                <x-filament::section heading="Endpoints" compact>
                    <div class="flex flex-col gap-4">
                        @forelse ($endpoints as $group => $groupEndpoints)
                            <div class="flex flex-col gap-2">
                                <div class="px-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ $group }}
                                </div>

                                <div class="flex flex-col gap-1">
                                    @foreach ($groupEndpoints as $endpoint)
                                        <a
                                            href="#{{ $endpoint->id }}"
                                            class="rounded-md px-2 py-2 text-sm text-gray-700 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white"
                                        >
                                            <span class="flex items-center gap-2">
                                                <x-filament::badge
                                                    :color="match ($endpoint->method) {
                                                        'GET' => 'success',
                                                        'POST' => 'info',
                                                        'PUT', 'PATCH' => 'warning',
                                                        'DELETE' => 'danger',
                                                        default => 'gray',
                                                    }"
                                                    size="xs"
                                                    class="w-12 justify-center"
                                                >
                                                    {{ $endpoint->method }}
                                                </x-filament::badge>

                                                <span class="truncate font-mono text-xs">{{ $endpoint->path }}</span>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
                                No endpoints found.
                            </div>
                        @endforelse
                    </div>
                </x-filament::section>
            </aside>

            <main class="flex min-w-0 flex-col gap-6">
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
    </div>
</x-filament-panels::page>

@php
    $methodColor = match ($endpoint->method) {
        'GET' => 'success',
        'POST' => 'info',
        'PUT', 'PATCH' => 'warning',
        'DELETE' => 'danger',
        default => 'gray',
    };
@endphp

<x-filament::section
    :id="$endpoint->id"
    :heading="$endpoint->title()"
    :description="$endpoint->description"
    collapsible
    persist-collapsed
    class="scroll-mt-6"
>
    <x-slot name="afterHeader">
        <div class="flex flex-wrap items-center gap-2">
            <x-filament::badge :color="$methodColor">
                {{ $endpoint->method }}
            </x-filament::badge>

            @if ($endpoint->deprecated)
                <x-filament::badge color="danger">
                    Deprecated
                </x-filament::badge>
            @endif
        </div>
    </x-slot>

    <div class="flex flex-col gap-5">
        <label class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-950 dark:text-white">Path</span>

            <x-filament::input.wrapper
                :prefix="$endpoint->method"
                prefix-icon="heroicon-o-link"
            >
                <x-filament::input
                    :value="$endpoint->path"
                    readonly
                    class="font-mono text-sm"
                />
            </x-filament::input.wrapper>
        </label>

        @if ($endpoint->parameters !== [])
            <x-filament::section
                heading="Parameters"
                :description="count($endpoint->parameters).' documented parameters'"
                collapsible
                collapsed
                secondary
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                                <th class="py-2 pr-4">Name</th>
                                <th class="py-2 pr-4">Location</th>
                                <th class="py-2 pr-4">Type</th>
                                <th class="py-2 pr-4">Required</th>
                                <th class="py-2">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($endpoint->parameters as $parameter)
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="py-2 pr-4">
                                        <code class="text-xs font-semibold text-gray-950 dark:text-white">{{ $parameter['name'] }}</code>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <x-filament::badge color="gray" size="xs">{{ $parameter['in'] }}</x-filament::badge>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <x-filament::badge color="info" size="xs">{{ $parameter['type'] }}</x-filament::badge>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <x-filament::badge :color="$parameter['required'] ? 'danger' : 'gray'" size="xs">
                                            {{ $parameter['required'] ? 'Required' : 'Optional' }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="py-2">{{ $parameter['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if ($endpoint->hasRequestBody())
            <x-filament::section
                heading="Request body"
                collapsible
                collapsed
                secondary
            >
                <div class="flex flex-col gap-4">
                    @foreach ($endpoint->requestBodies as $body)
                        <div class="flex flex-col gap-2">
                            <label class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-gray-950 dark:text-white">Content type</span>

                                <x-filament::input.wrapper prefix-icon="heroicon-o-code-bracket">
                                    <x-filament::input
                                        :value="$body['contentType']"
                                        readonly
                                        class="font-mono text-xs"
                                    />
                                </x-filament::input.wrapper>
                            </label>

                            @include('filament-openapi-docs::components.schema', ['schema' => $body['schema']])
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        @if ($endpoint->responses !== [])
            <x-filament::section
                heading="Responses"
                :description="count($endpoint->responses).' documented responses'"
                collapsible
                secondary
            >
                <div class="flex flex-col gap-4">
                    @foreach ($endpoint->responses as $status => $response)
                        @php
                            $statusColor = match (true) {
                                str_starts_with((string) $status, '2') => 'success',
                                str_starts_with((string) $status, '3'), str_starts_with((string) $status, '4') => 'warning',
                                str_starts_with((string) $status, '5') => 'danger',
                                default => 'gray',
                            };
                        @endphp

                        <x-filament::section
                            :heading="$response['description'] ?: 'Response'"
                            collapsible
                            collapsed
                            compact
                            secondary
                        >
                            <x-slot name="afterHeader">
                                <x-filament::badge :color="$statusColor">
                                    {{ $status }}
                                </x-filament::badge>
                            </x-slot>

                            @if ($response['content'] !== [])
                                <div class="flex flex-col gap-4">
                                    @foreach ($response['content'] as $contentType => $schema)
                                        <div class="flex flex-col gap-2">
                                            <label class="flex flex-col gap-1">
                                                <span class="text-sm font-medium text-gray-950 dark:text-white">Content type</span>

                                                <x-filament::input.wrapper prefix-icon="heroicon-o-code-bracket">
                                                    <x-filament::input
                                                        :value="$contentType"
                                                        readonly
                                                        class="font-mono text-xs"
                                                    />
                                                </x-filament::input.wrapper>
                                            </label>

                                            @include('filament-openapi-docs::components.schema', ['schema' => $schema])
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No response body documented.</p>
                            @endif
                        </x-filament::section>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament::section>

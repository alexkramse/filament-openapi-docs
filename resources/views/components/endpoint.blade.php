@php
    $methodColor = match ($endpoint->method) {
        'GET' => 'success',
        'POST' => 'info',
        'PUT', 'PATCH' => 'warning',
        'DELETE' => 'danger',
        default => 'gray',
    };

    $queryParameters = collect($endpoint->parameters)->where('in', 'query')->values();
    $pathParameters = collect($endpoint->parameters)->where('in', 'path')->values();
    $headerParameters = collect($endpoint->parameters)->where('in', 'header')->values();
@endphp

<div id="{{ $endpoint->id }}" class="scroll-mt-6">
    <x-filament::section
        :heading="$endpoint->title()"
        :description="$endpoint->description"
        icon="heroicon-o-command-line"
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
                <span class="text-sm font-medium text-gray-950 dark:text-white">Endpoint</span>

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

            <x-filament::section heading="Request data" collapsible secondary>
                <div class="flex flex-col gap-4">
                    @if ($pathParameters->isNotEmpty())
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-gray-950 dark:text-white">Path parameters</h4>

                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($pathParameters as $parameter)
                                    @include('filament-openapi-docs::components.parameter-field', ['parameter' => $parameter])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($headerParameters->isNotEmpty())
                        <div class="flex flex-col gap-2">
                            <h4 class="text-sm font-semibold text-gray-950 dark:text-white">Headers</h4>

                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($headerParameters as $parameter)
                                    @include('filament-openapi-docs::components.parameter-field', ['parameter' => $parameter])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($endpoint->hasRequestBody())
                        <div class="flex flex-col gap-3">
                            <h4 class="text-sm font-semibold text-gray-950 dark:text-white">Body</h4>

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
                    @endif

                    @if ($pathParameters->isEmpty() && $headerParameters->isEmpty() && ! $endpoint->hasRequestBody())
                        <p class="text-sm text-gray-500 dark:text-gray-400">No request data documented.</p>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Query parameters"
                :description="$queryParameters->count().' documented query parameters'"
                collapsible
                :collapsed="$queryParameters->isEmpty()"
                secondary
            >
                @if ($queryParameters->isNotEmpty())
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($queryParameters as $parameter)
                            @include('filament-openapi-docs::components.parameter-field', ['parameter' => $parameter])
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No query parameters documented.</p>
                @endif
            </x-filament::section>

            <x-filament::section
                heading="Responses"
                :description="count($endpoint->responses).' documented responses'"
                collapsible
                secondary
            >
                @if ($endpoint->responses !== [])
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
                                                    <span class="text-sm font-medium text-gray-950 dark:text-white">Body content type</span>

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
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No responses documented.</p>
                @endif
            </x-filament::section>
        </div>
    </x-filament::section>
</div>

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
    $examplePresenter = app(\Kramarenko\FilamentOpenApiDocs\Services\ExamplePresenter::class);
    $schemaComponents = $components ?? [];
    $documentedServers = $servers ?? [];
@endphp

<div id="{{ $endpoint->id }}" style="scroll-margin-top: 1.5rem;">

    <x-filament::section
        :heading="$endpoint->title()"
    >
        <x-slot name="description">
            <p class="fi-section-header-description">{{$endpoint->description}}</p>

            <div class="foad-inline-list">
                <x-filament::badge :color="$methodColor">
                    {{ $endpoint->method }}
                </x-filament::badge>

                <x-filament::badge color="gray">
                    {{ $endpoint->path }}
                </x-filament::badge>

                @if ($endpoint->deprecated)
                    <x-filament::badge color="danger">
                        Deprecated
                    </x-filament::badge>
                @endif

                @if ($documentedServers !== [])
                    <div class="foad-grid">
                        @foreach ($documentedServers as $server)
                            <label class="fi-fo-field">
                                <x-filament::badge color="gray">
                                    {{ $server }}{{$endpoint->path}}
                                </x-filament::badge>
                            </label>
                        @endforeach
                    </div>
                @endif


            </div>
        </x-slot>

        <div class="foad-stack">
            <x-filament::section heading="Request" collapsible secondary>
                <div class="foad-stack foad-stack-md">
                    @include('filament-openapi-docs::components.request-snippet', [
                        'endpoint' => $endpoint,
                        'servers' => $documentedServers,
                        'components' => $schemaComponents,
                    ])

                    @if ($headerParameters->isNotEmpty())
                        <div class="foad-stack foad-stack-sm">
                            <h4 class="fi-section-header-heading">Headers</h4>

                            <div class="foad-grid">
                                @foreach ($headerParameters as $parameter)
                                    @include('filament-openapi-docs::components.parameter-field', ['parameter' => $parameter])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($endpoint->hasRequestBody())
                        <div class="foad-stack foad-stack-md">
                            <h4 class="fi-section-header-heading">Body</h4>

                            @foreach ($endpoint->requestBodies as $body)
                                <div class="foad-stack foad-stack-sm">
                                    <div class="foad-inline-list foad-inline-list-sm">
                                        <span class="foad-property-meta-label">Body</span>
                                        <x-filament::badge color="gray" size="xs">{{ $body['contentType'] }}</x-filament::badge>
                                    </div>

                                    @include('filament-openapi-docs::components.sample', [
                                        'label' => 'Request Sample',
                                        'contentType' => $body['contentType'],
                                        'samples' => $examplePresenter->samples($body, $schemaComponents),
                                    ])

                                    @include('filament-openapi-docs::components.schema', ['schema' => $body['schema'], 'components' => $schemaComponents])
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($pathParameters->isEmpty() && $headerParameters->isEmpty() && ! $endpoint->hasRequestBody())
                        <p class="fi-section-header-description">No request data documented.</p>
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
                    <div class="foad-grid">
                        @foreach ($queryParameters as $parameter)
                            @include('filament-openapi-docs::components.parameter-field', ['parameter' => $parameter])
                        @endforeach
                    </div>
                @else
                    <p class="fi-section-header-description">No query parameters documented.</p>
                @endif
            </x-filament::section>

            <div class="fi-section-ctn foad-stack">
                <x-filament::section heading="Responses" :description="count($endpoint->responses).' documented responses'"
                                     collapsible
                                     :collapsed="$endpoint->responses === []"
                                     secondary>
                    @foreach ($endpoint->responses as $status => $response)
                        @php
                            $statusColor = match (true) {
                                str_starts_with((string) $status, '2') => 'success',
                                str_starts_with((string) $status, '3'), str_starts_with((string) $status, '4') => 'warning',
                                str_starts_with((string) $status, '5') => 'danger',
                                default => 'gray',
                            };
                        @endphp

                        <div class="foad-response-block">
                            <div class="foad-property-main">
                                <x-filament::badge :color="$statusColor">
                                    {{ $status }}
                                </x-filament::badge>
                                <h3 class="fi-section-header-heading">
                                    {{ $response['description'] ?: 'Response' }}
                                </h3>

                            </div>

                            @if ($response['content'] !== [])
                                <div class="foad-stack foad-stack-sm">
                                    @foreach ($response['content'] as $contentType => $mediaType)
                                        <div class="foad-stack foad-stack-sm">
                                            <div class="foad-inline-list foad-inline-list-sm">
                                                <span class="foad-property-meta-label">Body</span>
                                                <x-filament::badge color="gray" size="xs">{{ $contentType }}</x-filament::badge>
                                            </div>

                                            @include('filament-openapi-docs::components.sample', [
                                                'label' => 'Response Example',
                                                'contentType' => $contentType,
                                                'samples' => $examplePresenter->samples($mediaType, $schemaComponents),
                                            ])

                                            @include('filament-openapi-docs::components.schema', ['schema' => $mediaType['schema'], 'components' => $schemaComponents])
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="fi-section-header-description">No response body documented.</p>
                            @endif
                        </div>
                    @endforeach
                </x-filament::section>
            </div>
        </div>
    </x-filament::section>
</div>

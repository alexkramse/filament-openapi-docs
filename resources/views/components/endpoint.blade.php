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

<div id="{{ $endpoint->id }}" style="scroll-margin-top: 1.5rem;">
    <x-filament::section
        :heading="$endpoint->title()"

    >
        <x-slot name="description">
            <div class="foad-inline-list">
                <x-filament::badge :color="$methodColor">
                    {{ $endpoint->method }}
                </x-filament::badge>

                @if ($endpoint->deprecated)
                    <x-filament::badge color="danger">
                        Deprecated
                    </x-filament::badge>
                @endif

                <x-filament::badge color="gray">
                    {{$endpoint->path}}
                </x-filament::badge>
            </div>
            <p class="fi-section-header-description">{{$endpoint->description}}</p>
        </x-slot>

        <div class="foad-stack">

            <x-filament::section heading="Request data" collapsible secondary>
                <div class="foad-stack foad-stack-md">
                    @if ($pathParameters->isNotEmpty())
                        <div class="foad-stack foad-stack-sm">
                            <h4 class="fi-section-header-heading">Path parameters</h4>

                            <div class="foad-grid">
                                @foreach ($pathParameters as $parameter)
                                    @include('filament-openapi-docs::components.parameter-field', ['parameter' => $parameter])
                                @endforeach
                            </div>
                        </div>
                    @endif

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
                                    <label class="fi-fo-field">
                                        <span class="fi-fo-field-label-content">Content type</span>

                                        <x-filament::input.wrapper prefix-icon="heroicon-o-code-bracket">
                                            <x-filament::input
                                                :value="$body['contentType']"
                                                readonly
                                            />
                                        </x-filament::input.wrapper>
                                    </label>

                                    @include('filament-openapi-docs::components.schema', ['schema' => $body['schema']])
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
                <x-filament::section
                    heading="Responses"
                    :description="count($endpoint->responses).' documented responses'"
                    collapsible
                    secondary
                >
                    <x-filament::tabs>
                        @foreach ($endpoint->responses as $status => $response)
                            @php
                                $statusColor = match (true) {
                                    str_starts_with((string) $status, '2') => 'success',
                                    str_starts_with((string) $status, '3'), str_starts_with((string) $status, '4') => 'warning',
                                    str_starts_with((string) $status, '5') => 'danger',
                                    default => 'gray',
                                };
                            @endphp
                            <x-filament::tabs.item
                                alpine-active="activeTab === '{{ $status }}'"
                                x-on:click="activeTab = '{{ $status }}'"
                            >
                                <x-filament::badge :color="$statusColor">
                                    {{ $status }}
                                </x-filament::badge>
                            </x-filament::tabs.item>
                        @endforeach
                    </x-filament::tabs>

                    @foreach ($endpoint->responses as $status => $response)
                        <div x-show="activeTab === '{{ $status }}'">
                            <h3 class="fi-section-header-heading">
                                {{ $response['description'] ?: 'Response'  }}
                            </h3>

                            @if ($response['content'] !== [])
                                <div class="foad-stack foad-stack-md">
                                    @foreach ($response['content'] as $contentType => $schema)
                                        <div class="foad-stack foad-stack-sm">
                                            <label class="fi-fo-field">
                                                <span class="fi-fo-field-label-content">Body content type</span>

                                                <x-filament::input.wrapper prefix-icon="heroicon-o-code-bracket">
                                                    <x-filament::input
                                                        :value="$contentType"
                                                        readonly
                                                    />
                                                </x-filament::input.wrapper>
                                            </label>

                                            @include('filament-openapi-docs::components.schema', ['schema' => $schema])
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

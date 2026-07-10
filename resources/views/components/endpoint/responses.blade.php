<div class="fi-section-ctn foad-stack">
    <x-filament::section
        heading="Responses"
        :description="count($endpoint->responses).' documented responses'"
        collapsible
        :collapsed="$endpoint->responses === []"
        secondary
    >
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

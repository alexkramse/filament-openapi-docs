<x-filament::section
    heading="Responses"
    :description="count($endpoint->responses).' documented responses'"
    collapsible
    collapsed
>
    <x-slot name="heading">
        <div class="foad-justify-content-space-between">
            <span>Responses</span>
            <div>
                @foreach ($endpoint->responses as $status => $response)
                    @php
                        $statusColor = match (true) {
                            str_starts_with((string) $status, '2') => 'success',
                            str_starts_with((string) $status, '3'), str_starts_with((string) $status, '4') => 'warning',
                            str_starts_with((string) $status, '5') => 'danger',
                            default => 'gray',
                        };
                    @endphp
                    <x-filament::badge :color="$statusColor">
                        {{ $status }}
                    </x-filament::badge>
                @endforeach</div>
        </div>
    </x-slot>
    <x-slot name="description">
        <div class="fi-section-header-description fi-flex">
            <span>{{count($endpoint->responses).' documented responses'}}</span>
        </div>
    </x-slot>

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
                                <x-filament::badge color="gray" size="md">
                                    Type: {{ $contentType }}</x-filament::badge>
                            </div>
                            <div class="fi-sc-component">
                                <div x-data="{ tab: 'tab2' }" class="fi-sc-tabs fi-contained">
                                    <x-filament::tabs label="Content Tabs" class="fi-contained">
                                        <x-filament::tabs.item
                                            @click="tab = 'tab2'"
                                            :alpine-active="'tab === \'tab2\''"
                                            active
                                        >
                                            Tree view
                                        </x-filament::tabs.item>
                                        <x-filament::tabs.item
                                            @click="tab = 'tab1'"
                                            :alpine-active="'tab === \'tab1\''"

                                        >
                                            JSON
                                        </x-filament::tabs.item>

                                    </x-filament::tabs>

                                    <div class="fi-sc-tabs-tab fi-active">
                                        <div x-show="tab === 'tab2'">
                                            @include('filament-openapi-docs::components.schema', ['schema' => $mediaType['schema'], 'components' => $schemaComponents])

                                        </div>
                                        <div x-show="tab === 'tab1'" x-cloak>
                                            @include('filament-openapi-docs::components.sample', [
                                    'label' => 'Response Example',
                                    'contentType' => $contentType,
                                    'samples' => $examplePresenter->samples($mediaType, $schemaComponents),
                                ])
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="fi-section-header-description">No response body documented.</p>
            @endif
        </div>
    @endforeach
</x-filament::section>


<div class="foad-stack foad-stack-md">
    @if ($requestData['securityItems'] !== [])
        <div class="foad-stack foad-stack-sm">
            <h4 class="fi-section-header-heading">Security</h4>
            <div class="foad-grid">
                @foreach ($requestData['securityItems'] as $securityItem)
                    @include('filament-openapi-docs::components.request-doc-item', [
                        'title' => $securityItem['label'],
                        'description' => $securityItem['description'],
                        'badges' => [],
                        'metadata' => [
                            \Illuminate\Support\Str::headline($securityItem['location']) => $securityItem['name'],
                            'Example' => $securityItem['documentationExample'] ?? $securityItem['value'],
                        ],
                    ])
                @endforeach
            </div>
        </div>
    @endif

    @if ($requestData['mediaHeaders'] !== [])
        <div class="foad-stack foad-stack-sm">
            <h4 class="fi-section-header-heading">Media headers</h4>
            <div class="foad-grid">
                @foreach ($requestData['mediaHeaders'] as $mediaHeader)
                    @include('filament-openapi-docs::components.request-doc-item', [
                        'title' => $mediaHeader['name'].': '.$mediaHeader['value'],
                        'description' => $mediaHeader['description'],
                        'badges' => [],
                        'metadata' => [],
                    ])
                @endforeach
            </div>
        </div>
    @endif

    @foreach ([
        'Headers' => $requestData['headerParameters'],
        'Path parameters' => $requestData['pathParameters'],
        'Query parameters' => $requestData['queryParameters'],
    ] as $heading => $parameters)
        @if ($parameters !== [])
            <div class="foad-stack foad-stack-sm">
                <h4 class="fi-section-header-heading">{{ $heading }}</h4>
                <div class="foad-grid">
                    @foreach ($parameters as $parameter)
                        @include('filament-openapi-docs::components.request-doc-item', [
                            'title' => $parameter['name'],
                            'description' => $parameter['description'],
                            'badges' => [
                                ['label' => $parameter['type'], 'color' => 'info'],
                                ...(filled($parameter['value'] ?? null) ? [['label' => 'example: '.$parameter['value'], 'color' => 'primary']] : []),
                                ['label' => $parameter['required'] ? 'Required' : 'Optional', 'color' => $parameter['required'] ? 'danger' : 'gray'],
                            ],
                            'metadata' => [
                                'Example' => $parameter['example'] ?? null,
                            ],
                        ])
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    @if ($endpoint->hasRequestBody())
        <div class="foad-stack foad-stack-md">
            <h4 class="fi-section-header-heading">Body</h4>

            @foreach ($endpoint->requestBodies as $body)
                <div class="foad-stack foad-stack-sm">
                    <div class="foad-inline-list foad-inline-list-sm">
                        <x-filament::badge color="gray" size="md">
                            Type: {{ $body['contentType'] }}
                        </x-filament::badge>
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
                                    @include('filament-openapi-docs::components.schema', ['schema' => $body['schema'], 'components' => $components])
                                </div>

                                <div x-show="tab === 'tab1'" x-cloak>
                                    @include('filament-openapi-docs::components.sample', [
                                        'label' => 'Request Sample',
                                        'contentType' => $body['contentType'],
                                        'samples' => $examplePresenter->samples($body, $components),
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($requestData['hasRequestSamples'])
        @include('filament-openapi-docs::components.request-snippet', [
            'requestData' => $requestData,
        ])
    @endif

    @if ($requestData['securityItems'] === [] && $requestData['mediaHeaders'] === [] && $requestData['headerParameters'] === [] && $requestData['pathParameters'] === [] && $requestData['queryParameters'] === [] && ! $endpoint->hasRequestBody())
        <p class="fi-section-header-description">No request data documented.</p>
    @endif
</div>

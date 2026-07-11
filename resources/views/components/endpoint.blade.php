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
    $requestSnippetData = app(\Kramarenko\FilamentOpenApiDocs\Services\RequestSnippetPresenter::class)
        ->present($endpoint, $documentedServers, $schemaComponents);
    $hasRequestSamples = config('filament-openapi-docs.request_samples.enabled', true) && $requestSnippetData['requests'] !== [];
@endphp

<div id="{{ $endpoint->id }}" style="scroll-margin-top: 1.5rem;">

    <x-filament::section
        :heading="$endpoint->title()"
    >


        <x-slot name="heading">

            <div class="foad-justify-content-space-between">
                <div class="foad-property-main">

                    @if ($endpoint->deprecated)
                        <x-filament::badge color="danger">
                            Deprecated
                        </x-filament::badge>
                    @endif

                    <x-filament::badge :color="$methodColor">
                        {{ $endpoint->method }}
                    </x-filament::badge>

                    <h2 class="fi-section-header-heading">{{$endpoint->title()}}</h2>
                </div>
                <x-filament::badge color="gray">
                    {{ $endpoint->path }}
                </x-filament::badge>
            </div>

            {{--  <div class="foad-inline-list">
               {{--  @documentedServers !== [])
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
                    </div>--}}
        </x-slot>
        <x-slot name="description">
            <p class="fi-section-header-description">{{$endpoint->description}}</p>
        </x-slot>

        <div class="foad-stack">
            <div
                @if ($hasRequestSamples)
                    x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('request-snippet', 'alexkramse/filament-openapi-docs') }}"
                x-data="requestSnippet(@js($requestSnippetData))"
                @endif
            >
                <x-filament::section heading="Request" collapsible secondary>
                    @if ($hasRequestSamples)
                        <x-slot name="afterHeader">
                            <label class="foad-developer-mode fi-fo-toggle">
                                <x-filament::input.checkbox class="foad-developer-mode-input" x-model="developerMode"/>
                                <span class="foad-developer-mode-switch" aria-hidden="true">
                                    <span class="foad-developer-mode-knob"></span>
                                </span>
                                <span class="fi-fo-field-label-content">Developer mode</span>
                            </label>
                        </x-slot>
                    @endif

                    <div class="foad-stack foad-stack-md">
                        @include('filament-openapi-docs::components.request-snippet', [
                            'endpoint' => $endpoint,
                            'servers' => $documentedServers,
                            'components' => $schemaComponents,
                            'requestSnippetData' => $requestSnippetData,
                            'usesExternalRequestSnippetState' => $hasRequestSamples,
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
                                            <x-filament::badge color="gray" size="md">
                                                Type: {{ $body['contentType'] }}</x-filament::badge>
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
                                                        @include('filament-openapi-docs::components.schema', ['schema' => $body['schema'], 'components' => $schemaComponents])
                                                    </div>
                                                    <div x-show="tab === 'tab1'" x-cloak>

                                                        @include('filament-openapi-docs::components.sample', [
                                                            'label' => 'Request Sample',
                                                            'contentType' => $body['contentType'],
                                                            'samples' => $examplePresenter->samples($body, $schemaComponents),
                                                        ])
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($pathParameters->isEmpty() && $headerParameters->isEmpty() && ! $endpoint->hasRequestBody())
                            <p class="fi-section-header-description">No request data documented.</p>
                        @endif
                    </div>
                </x-filament::section>
            </div>

            {{--            <x-filament::section
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
                        </x-filament::section>--}}

            @include('filament-openapi-docs::components.endpoint.responses', [
                'endpoint' => $endpoint,
                'examplePresenter' => $examplePresenter,
                'schemaComponents' => $schemaComponents,
            ])
        </div>
    </x-filament::section>
</div>

@php
    $methodColor = \Alexkramse\FilamentOpenapiDocs\Enums\HttpMethod::color($endpoint->method);
    $examplePresenter = app(\Alexkramse\FilamentOpenapiDocs\Services\ExamplePresenter::class);
    $schemaComponents = $components ?? [];
    $documentedServers = $servers ?? [];
    $requestData = app(\Alexkramse\FilamentOpenapiDocs\Services\RequestSnippetPresenter::class)
        ->present($endpoint, $documentedServers, $schemaComponents);
@endphp

<div id="{{ $endpoint->id }}" class="foad-stack"
     @if ($requestData['hasRequestSamples'])
         x-load
     x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('request-snippet', 'alexkramse/filament-openapi-docs') }}"
     x-data="requestSnippet(@js($requestData))"
     @else
         x-data="{ sendMode: false, developerMode: false }"
    @endif>
    <x-filament::section :heading="$endpoint->title()">
        <x-slot name="heading">
            <div class="foad-justify-content-space-between">
                <div class="foad-property-main">
                    @if ($endpoint->deprecated)
                        <x-filament::badge color="danger">
                            {{ __('filament-openapi-docs::ui.badges.deprecated') }}
                        </x-filament::badge>
                    @endif

                    <x-filament::badge :color="$methodColor">
                        {{ $endpoint->method }}
                    </x-filament::badge>
                    <x-filament::badge color="gray">
                        {{ $endpoint->path }}
                    </x-filament::badge>
                    <h2 class="fi-section-header-heading">{{ $endpoint->title() }}</h2>
                </div>

            </div>
        </x-slot>

        <x-slot name="description">
            <p class="fi-section-header-description">{{ $endpoint->description }}</p>
        </x-slot>
    </x-filament::section>

    <x-filament::section class="foad-request-section" :heading="__('filament-openapi-docs::ui.labels.request')" collapsible>
        @if ($requestData['hasRequestSamples'])
            <x-slot name="afterHeader">
                <div class="foad-request-mode-controls">
                    <label class="foad-developer-mode fi-fo-toggle">
                        <x-filament::input.checkbox class="foad-developer-mode-input" x-model="sendMode"/>
                        <span class="foad-developer-mode-switch" aria-hidden="true">
                                    <span class="foad-developer-mode-knob"></span>
                                </span>
                        <span class="fi-fo-field-label-content">{{ __('filament-openapi-docs::ui.labels.send_mode') }}</span>
                    </label>

                    <label class="foad-developer-mode fi-fo-toggle" x-show="sendMode" x-cloak>
                        <x-filament::input.checkbox class="foad-developer-mode-input" x-model="developerMode"/>
                        <span class="foad-developer-mode-switch" aria-hidden="true">
                                    <span class="foad-developer-mode-knob"></span>
                                </span>
                        <span class="fi-fo-field-label-content">{{ __('filament-openapi-docs::ui.labels.developer_mode') }}</span>
                    </label>
                </div>
            </x-slot>
        @endif

        <div x-show="! sendMode">
            @include('filament-openapi-docs::components.endpoint.request.read', [
                'endpoint' => $endpoint,
                'components' => $schemaComponents,
                'examplePresenter' => $examplePresenter,
                'requestData' => $requestData,
            ])
        </div>

        @if ($requestData['hasRequestSamples'])
            <div x-show="sendMode" x-cloak>
                @include('filament-openapi-docs::components.endpoint.request.send', [
                    'requestData' => $requestData,
                ])
            </div>
        @endif
    </x-filament::section>

    <x-filament::section :heading="__('filament-openapi-docs::ui.labels.request_sample')" collapsible collapsed>
        @include('filament-openapi-docs::components.request-snippet', [
            'requestData' => $requestData,
        ])
    </x-filament::section>

    @include('filament-openapi-docs::components.endpoint.responses', [
        'endpoint' => $endpoint,
        'components' => $schemaComponents,
        'examplePresenter' => $examplePresenter,
    ])
</div>

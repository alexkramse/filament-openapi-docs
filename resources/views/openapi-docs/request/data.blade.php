<div class="foad-stack foad-stack-md">
  <div
    class="fi-grid foad-send-layout md:fi-grid-cols"
    style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-md: repeat(2, minmax(0, 1fr));"
  >
    <div class="foad-stack foad-stack-sm">
      @if ($requestData['securityItems'] !== [])
        <div class="foad-stack foad-stack-sm">
          <h4 class="fi-section-header-heading">
            {{ __('filament-openapi-docs::ui.labels.security') }}
          </h4>
          <div class="">
            @foreach ($requestData['securityItems'] as $securityItem)
              @include ('filament-openapi-docs::openapi-docs.request.data.item', [
                            'title' => $securityItem['name'].': '.$securityItem['value'],
                            'description' => $securityItem['description'],
                            'badges' => [],
                            'metadata' => [
                                __('filament-openapi-docs::ui.labels.example') => $securityItem['documentationExample'] ?? $securityItem['value'] ?? null,
                            ],
                        ])
            @endforeach
          </div>
        </div>
      @endif

      @if ($requestData['mediaHeaders'] !== [] || $requestData['headerParameters'] !== [])
        <div class="foad-stack foad-stack-sm">
          <h4 class="fi-section-header-heading">
            {{ __('filament-openapi-docs::ui.labels.headers') }}
          </h4>
          <div class="">
            @foreach ($requestData['mediaHeaders'] as $mediaHeader)
              @include ('filament-openapi-docs::openapi-docs.request.data.item', [
                            'title' => $mediaHeader['name'].': '.$mediaHeader['value'],
                            'description' => $mediaHeader['description'],
                            'badges' => [],
                            'metadata' => [],
                        ])
            @endforeach

            @foreach ($requestData['headerParameters'] as $parameter)
              @include ('filament-openapi-docs::openapi-docs.request.data.item', [
                            'title' => $parameter['name'],
                            'description' => $parameter['description'],
                            'badges' => [
                                ['label' => $parameter['type'], 'color' => 'info'],
                                ...(filled($parameter['value'] ?? null) ? [['label' => \Illuminate\Support\Str::lower(__('filament-openapi-docs::ui.labels.example')).': '.$parameter['value'], 'color' => 'primary']] : []),
                                ['label' => $parameter['required'] ? __('filament-openapi-docs::ui.badges.required') : __('filament-openapi-docs::ui.badges.optional'), 'color' => $parameter['required'] ? 'danger' : 'gray'],
                            ],
                            'metadata' => [
                                __('filament-openapi-docs::ui.labels.example') => $parameter['example'] ?? null,
                            ],
                        ])
            @endforeach
          </div>
        </div>
      @endif

      @foreach ([
            __('filament-openapi-docs::ui.labels.path_parameters') => $requestData['pathParameters'],
            __('filament-openapi-docs::ui.labels.query_parameters') => $requestData['queryParameters'],
        ] as $heading => $parameters)
        @if ($parameters !== [])
          <div class="foad-stack foad-stack-sm">
            <h4 class="fi-section-header-heading">{{ $heading }}</h4>
            <div class="">
              @foreach ($parameters as $parameter)
                @include ('filament-openapi-docs::openapi-docs.request.data.item', [
                                'title' => $parameter['name'],
                                'description' => $parameter['description'],
                                'badges' => [
                                    ['label' => $parameter['type'], 'color' => 'info'],
                                    ...(filled($parameter['value'] ?? null) ? [['label' => \Illuminate\Support\Str::lower(__('filament-openapi-docs::ui.labels.example')).': '.$parameter['value'], 'color' => 'primary']] : []),
                                    ['label' => $parameter['required'] ? __('filament-openapi-docs::ui.badges.required') : __('filament-openapi-docs::ui.badges.optional'), 'color' => $parameter['required'] ? 'danger' : 'gray'],
                                ],
                                'metadata' => [
                                    __('filament-openapi-docs::ui.labels.example') => $parameter['example'] ?? null,
                                ],
                            ])
              @endforeach
            </div>
          </div>
        @endif
      @endforeach
    </div>

    <div class="foad-stack foad-stack-sm">
      @if ($endpoint->hasRequestBody())
        <div class="foad-stack foad-stack-md">
          <h4 class="fi-section-header-heading">
            {{ __('filament-openapi-docs::ui.labels.body') }}
          </h4>

          @foreach ($endpoint->requestBodies as $body)
            <x-filament-openapi-docs::media-type-content
              :label="__('filament-openapi-docs::ui.labels.body')"
              :content-type="$body['contentType']"
              :schema="$body['schema']"
              :components="$components"
              :samples="$examplePresenter->samples($body, $components)"
            />
          @endforeach
        </div>
      @endif

      @if ($requestData['securityItems'] === [] && $requestData['mediaHeaders'] === [] && $requestData['headerParameters'] === [] && $requestData['pathParameters'] === [] && $requestData['queryParameters'] === [] && ! $endpoint->hasRequestBody())
        <p class="fi-section-header-description">{{ __('filament-openapi-docs::ui.empty.request_data') }}</p>
      @endif
    </div>
  </div>
</div>

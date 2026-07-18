<div class="foad-stack foad-stack-md">
  @if ($requestData['securityItems'] !== [])
    <div class="foad-stack foad-stack-sm">
      <h4 class="fi-section-header-heading">
        {{ __('filament-openapi-docs::ui.labels.security') }}
      </h4>
      <div class="">
        @foreach ($requestData['securityItems'] as $securityItem)
          @include ('filament-openapi-docs::components.request-doc-item', [
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

  @if ($requestData['mediaHeaders'] !== [])
    <div class="foad-stack foad-stack-sm">
      <h4 class="fi-section-header-heading">
        {{ __('filament-openapi-docs::ui.labels.media_headers') }}
      </h4>
      <div class="">
        @foreach ($requestData['mediaHeaders'] as $mediaHeader)
          @include ('filament-openapi-docs::components.request-doc-item', [
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
        __('filament-openapi-docs::ui.labels.headers') => $requestData['headerParameters'],
        __('filament-openapi-docs::ui.labels.path_parameters') => $requestData['pathParameters'],
        __('filament-openapi-docs::ui.labels.query_parameters') => $requestData['queryParameters'],
    ] as $heading => $parameters)
    @if ($parameters !== [])
      <div class="foad-stack foad-stack-sm">
        <h4 class="fi-section-header-heading">{{ $heading }}</h4>
        <div class="">
          @foreach ($parameters as $parameter)
            @include ('filament-openapi-docs::components.request-doc-item', [
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

  @if ($endpoint->hasRequestBody())
    <div class="foad-stack foad-stack-md">
      <h4 class="fi-section-header-heading">
        {{ __('filament-openapi-docs::ui.labels.body') }}
      </h4>

      @foreach ($endpoint->requestBodies as $body)
        <div class="foad-stack foad-stack-sm">
          <div class="foad-inline-list foad-inline-list-sm">
            <x-filament::badge color="gray" size="md">
              {{ __('filament-openapi-docs::ui.badges.type', ['type' => $body['contentType']]) }}
            </x-filament::badge>
          </div>

          <div class="fi-sc-component">
            <div x-data="{ tab: 'tab2' }" class="fi-sc-tabs fi-contained">
              <x-filament::tabs
                :label="__('filament-openapi-docs::ui.labels.body')"
                class="fi-contained"
              >
                <x-filament::tabs.item
                  @click="tab = 'tab2'"
                  :alpine-active="'tab === \'tab2\''"
                  active
                >
                  {{ __('filament-openapi-docs::ui.labels.tree_view') }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                  @click="tab = 'tab1'"
                  :alpine-active="'tab === \'tab1\''"
                >
                  {{ __('filament-openapi-docs::ui.labels.json') }}
                </x-filament::tabs.item>
              </x-filament::tabs>

              <div class="fi-sc-tabs-tab fi-active">
                <div x-show="tab === 'tab2'">
                  @include ('filament-openapi-docs::components.schema', ['schema' => $body['schema'], 'components' => $components])
                </div>

                <div x-show="tab === 'tab1'" x-cloak>
                  @include ('filament-openapi-docs::components.sample', [
                                        'label' => __('filament-openapi-docs::ui.labels.request_sample_label'),
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

  @if ($requestData['securityItems'] === [] && $requestData['mediaHeaders'] === [] && $requestData['headerParameters'] === [] && $requestData['pathParameters'] === [] && $requestData['queryParameters'] === [] && ! $endpoint->hasRequestBody())
    <p class="fi-section-header-description">{{ __('filament-openapi-docs::ui.empty.request_data') }}</p>
  @endif
</div>

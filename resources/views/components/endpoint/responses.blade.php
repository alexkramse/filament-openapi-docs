<x-filament::section
  :heading="__('filament-openapi-docs::ui.labels.responses')"
  :description="__('filament-openapi-docs::ui.meta.documented_responses', ['count' => count($endpoint->responses)])"
  collapsible
  collapsed
>
  <x-slot name="heading">
    {{ __('filament-openapi-docs::ui.labels.responses') }}
  </x-slot>
  <x-slot name="description">
    {{ __('filament-openapi-docs::ui.meta.documented_responses', ['count' => count($endpoint->responses)]) }}
    <div>
      @foreach ($endpoint->responses as $status => $response)
        <x-filament::badge
          :color="\Alexkramse\FilamentOpenapiDocs\Support\HttpStatus::color($status)"
        >
          {{ $status }}
        </x-filament::badge>
      @endforeach
    </div>
  </x-slot>
  @foreach ($endpoint->responses as $status => $response)
    <div class="foad-response-block">
      <div class="foad-property-main">
        <x-filament::badge
          :color="\Alexkramse\FilamentOpenapiDocs\Support\HttpStatus::color($status)"
        >
          {{ $status }}
        </x-filament::badge>
        <h3 class="fi-section-header-heading">
          {{ $response['description'] ?: __('filament-openapi-docs::ui.labels.response') }}
        </h3>
      </div>
      @if ($response['content'] !== [])
        <div class="foad-stack foad-stack-sm">
          @foreach ($response['content'] as $contentType => $mediaType)
            <div class="foad-stack foad-stack-sm">
              <div class="foad-inline-list foad-inline-list-sm">
                <x-filament::badge color="gray" size="md">
                  {{ __('filament-openapi-docs::ui.badges.type', ['type' => $contentType]) }}</x-filament::badge
                >
              </div>
              <div class="fi-sc-component">
                <div x-data="{ tab: 'tab2' }" class="fi-sc-tabs fi-contained">
                  <x-filament::tabs
                    :label="__('filament-openapi-docs::ui.labels.responses')"
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
                      @include ('filament-openapi-docs::components.schema', ['schema' => $mediaType['schema'], 'components' => $schemaComponents])
                    </div>
                    <div x-show="tab === 'tab1'" x-cloak>
                      @include ('filament-openapi-docs::components.sample', [
                                    'label' => __('filament-openapi-docs::ui.labels.response_example'),
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
        <p class="fi-section-header-description">{{ __('filament-openapi-docs::ui.empty.response_body') }}</p>
      @endif
    </div>
  @endforeach
</x-filament::section>

<x-filament::section
  :heading="__('filament-openapi-docs::ui.labels.responses')"
  collapsible
  collapsed
>
  <x-slot name="heading">
    <div class="foad-property-main">
      <x-filament::badge
        :color="\Alexkramse\FilamentOpenapiDocs\Support\HttpStatus::color($status)"
      >
        {{ $status }}
      </x-filament::badge>
      {{ $response['description'] ?: __('filament-openapi-docs::ui.labels.response') }}
    </div>
  </x-slot>

  <div class="foad-response-block">
    @if ($response['content'] !== [])
      <div class="foad-stack foad-stack-sm">
        @foreach ($response['content'] as $contentType => $mediaType)
          <x-filament-openapi-docs::media-type-content
            :label="__('filament-openapi-docs::ui.labels.responses')"
            :content-type="$contentType"
            :schema="$mediaType['schema']"
            :components="$schemaComponents"
            :samples="$examplePresenter->samples($mediaType, $schemaComponents)"
          />
        @endforeach
      </div>
    @else
      <p class="fi-section-header-description">{{ __('filament-openapi-docs::ui.empty.response_body') }}</p>
    @endif
  </div>
</x-filament::section>

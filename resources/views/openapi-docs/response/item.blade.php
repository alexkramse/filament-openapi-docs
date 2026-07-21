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
    @if (($response['headers'] ?? []) !== [])
      <div class="foad-stack foad-stack-sm">
        <h4 class="fi-section-header-heading">
          {{ __('filament-openapi-docs::ui.labels.headers') }}
        </h4>
        <div>
          @foreach ($response['headers'] as $header)
            @include ('filament-openapi-docs::openapi-docs.request.data.item', [
                'title' => $header['name'],
                'description' => $header['description'],
                'badges' => [
                    ['label' => $header['type'], 'color' => 'info'],
                    ...(array_key_exists('example', $header) ? [['label' => \Illuminate\Support\Str::lower(__('filament-openapi-docs::ui.labels.example')).': '.\Illuminate\Support\Str::limit((string) $header['example'], 80), 'color' => 'primary']] : []),
                    ['label' => $header['required'] ? __('filament-openapi-docs::ui.badges.required') : __('filament-openapi-docs::ui.badges.optional'), 'color' => $header['required'] ? 'danger' : 'gray'],
                ],
                'metadata' => [
                    __('filament-openapi-docs::ui.labels.value') => $header['example'] ?? null,
                ],
            ])
          @endforeach
        </div>
      </div>
    @endif

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

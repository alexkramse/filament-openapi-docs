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
      <div
        class="fi-grid foad-send-layout md:fi-grid-cols"
        style="
          --cols-default: repeat(1, minmax(0, 1fr));
          --cols-md: repeat(2, minmax(0, 1fr));
        "
      >
        <div class="foad-stack foad-stack-sm">
          @foreach ($response['content'] as $contentType => $mediaType)
            <div class="foad-stack foad-stack-sm">
              <div class="foad-property-main">
                <span class="foad-property-title">
                  <span class="foad-property-connector" aria-hidden="true"></span>
                  <span class="foad-property-name">{{ __('filament-openapi-docs::ui.labels.body') }}</span>
                </span>
                <div class="foad-inline-list foad-inline-list-sm">
                  <x-filament::badge color="gray" size="md">
                    {{ $contentType }}
                  </x-filament::badge>
                </div>
              </div>
              <x-filament-openapi-docs::schema-tree
                :schema="$mediaType['schema']"
                :components="$schemaComponents"
              />
            </div>
          @endforeach
        </div>
        <div class="foad-stack foad-stack-sm">
          @foreach ($response['content'] as $contentType => $mediaType)
            <div class="foad-stack foad-stack-sm">
              <div class="foad-property-main">
                <span class="foad-property-title">
                  <span class="foad-property-connector" aria-hidden="true"></span>
                  <span class="foad-property-name">{{ __('filament-openapi-docs::ui.labels.body') }}</span>
                </span>
                <div class="foad-inline-list foad-inline-list-sm">
                  <x-filament::badge color="gray" size="md">
                    {{ $contentType }}
                  </x-filament::badge>
                </div>
              </div>

              <x-filament-openapi-docs::code-sample
                :label="__('filament-openapi-docs::ui.labels.responses')"
                :content-type="$contentType"
                :samples="$examplePresenter->samples($mediaType, $schemaComponents)"
              />
            </div>
          @endforeach
        </div>
      </div>
    @else
      <p class="fi-section-header-description">{{ __('filament-openapi-docs::ui.empty.response_body') }}</p>
    @endif
  </div>
</x-filament::section>

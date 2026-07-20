<x-filament::section
  divided
  :heading="__('filament-openapi-docs::ui.labels.request')"
  compact
  :contained="false"
>
  @if ($requestData['hasRequestSamples'])
    <x-slot name="afterHeader">
      <div class="foad-request-mode-controls">
        <label class="foad-developer-mode fi-fo-toggle">
          <x-filament::input.checkbox
            class="foad-developer-mode-input"
            x-model="sendMode"
          />
          <span class="foad-developer-mode-switch" aria-hidden="true">
            <span class="foad-developer-mode-knob"></span>
          </span>
          <span
            class="fi-fo-field-label-content"
            >{{ __('filament-openapi-docs::ui.labels.send_mode') }}</span
          >
        </label>
      </div>
    </x-slot>
  @endif
</x-filament::section>

<x-filament::section
  :heading="__('filament-openapi-docs::ui.labels.request')"
  collapsible
>
  @if ($requestData['hasRequestSamples'])
    <x-slot name="afterHeader">
      <div class="foad-request-mode-controls">
        <label
          class="foad-developer-mode fi-fo-toggle"
          x-show="sendMode && hasDeveloperOptions"
          x-cloak
        >
          <x-filament::input.checkbox
            class="foad-developer-mode-input"
            x-model="developerMode"
          />
          <span class="foad-developer-mode-switch" aria-hidden="true">
            <span class="foad-developer-mode-knob"></span>
          </span>
          <span
            class="fi-fo-field-label-content"
            >{{ __('filament-openapi-docs::ui.labels.developer_mode') }}</span
          >
        </label>
      </div>
    </x-slot>
  @endif

  <div x-show="!sendMode">
    @include ('filament-openapi-docs::openapi-docs.request.data', [
        'endpoint' => $endpoint,
        'components' => $components,
        'examplePresenter' => $examplePresenter,
        'requestData' => $requestData,
    ])
  </div>

  @if ($requestData['hasRequestSamples'])
    <div x-show="sendMode" x-cloak>
      @include ('filament-openapi-docs::openapi-docs.request.tester', [
          'requestData' => $requestData,
      ])
    </div>
  @endif
</x-filament::section>

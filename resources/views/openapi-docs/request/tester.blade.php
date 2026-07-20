@php
  $sendingLabel = \Illuminate\Support\Js::from(__('filament-openapi-docs::ui.actions.sending'));
  $sendApiRequestLabel = \Illuminate\Support\Js::from(__('filament-openapi-docs::ui.actions.send_api_request'));
@endphp

<div class="foad-stack foad-stack-md">
  <div
    class="fi-grid foad-send-layout md:fi-grid-cols"
    style="
      --cols-default: repeat(1, minmax(0, 1fr));
      --cols-md: repeat(2, minmax(0, 1fr));
    "
  >
    <div class="foad-stack foad-stack-sm">
      @include ('filament-openapi-docs::openapi-docs.request.tester.auth')
      @include ('filament-openapi-docs::openapi-docs.request.tester.headers')
      @include ('filament-openapi-docs::openapi-docs.request.tester.cookies')
      @include ('filament-openapi-docs::openapi-docs.request.tester.path-parameters')
      @include ('filament-openapi-docs::openapi-docs.request.tester.query-parameters')
    </div>

    <div class="foad-stack foad-stack-sm">
      @include ('filament-openapi-docs::openapi-docs.request.tester.body')
      <template x-if="!hasRequestControls">
        <p class="fi-section-header-description">{{ __('filament-openapi-docs::ui.empty.request_data') }}</p>
      </template>
    </div>
  </div>

  <x-filament::button
    type="button"
    x-on:click="sendRequest()"
    x-bind:disabled="sending"
    x-text="sending ? {{ $sendingLabel }} : {{ $sendApiRequestLabel }}"
  />

  <template x-if="sendError">
    <p class="foad-sample-error foad-try-message" x-text="sendError"></p>
  </template>

  @include ('filament-openapi-docs::openapi-docs.request.tester.response-preview')
</div>

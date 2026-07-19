@php
  $sendingLabel = \Illuminate\Support\Js::from(__('filament-openapi-docs::ui.actions.sending'));
  $sendApiRequestLabel = \Illuminate\Support\Js::from(__('filament-openapi-docs::ui.actions.send_api_request'));
@endphp

<div class="foad-stack foad-stack-md">
  <div
    class="fi-grid foad-send-layout md:fi-grid-cols"
    style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-md: repeat(2, minmax(0, 1fr));"
  >
    <div class="foad-stack foad-stack-sm">
      @include ('filament-openapi-docs::components.request-snippet.auth')
      @include ('filament-openapi-docs::components.request-snippet.media-headers')
      @include ('filament-openapi-docs::components.request-snippet.headers')
      @include ('filament-openapi-docs::components.request-snippet.path-parameters')
      @include ('filament-openapi-docs::components.request-snippet.query-parameters')
    </div>

    <div class="foad-stack foad-stack-sm">
      @include ('filament-openapi-docs::components.request-snippet.body')
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

  <template x-if="response">
    <div class="foad-stack foad-stack-sm">
      <div class="foad-body-toolbar">
        <h4 class="fi-section-header-heading">
          <div class="foad-property-main">
            <x-filament::badge
              x-bind:data-color="responseStatusColor(response.status)"
              x-text="response.status"
            ></x-filament::badge>
            {{ __('filament-openapi-docs::ui.labels.response') }}
          </div>
        </h4>
        <x-filament::button
          size="xs"
          icon="heroicon-m-document-duplicate"
          icon-position="after"
          outlined
          x-on:click="copyResponseBody()"
        />
      </div>
      <p class="fi-section-header-description">
        <template x-if="response.contentType">
          <x-filament::badge color="gray" x-text="response.contentType"></x-filament::badge>
        </template>
        <template x-if="response.statusText">
          <x-filament::badge color="gray" x-text="response.statusText"></x-filament::badge>
        </template>
      </p>

      <div class="foad-response-block">
        <div class="foad-sample-scroll foad-response-code">
          <pre class="foad-sample-code"><code
              x-bind:class="`language-${responsePrismLanguage}`"
              x-html="highlightedResponseBody"
            ></code></pre>
        </div>
      </div>
    </div>
  </template>
</div>

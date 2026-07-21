<template x-if="response">
  <div class="foad-stack foad-stack-sm">
    <div class="foad-body-toolbar">
      <h4 class="fi-section-header-heading">
        <div class="foad-property-main">
          <x-filament::badge
            x-bind:class="responseStatusClass(response.status)"
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
        <x-filament::badge
          color="gray"
          x-text="response.contentType"
        ></x-filament::badge>
      </template>
      <template x-if="response.statusText">
        <x-filament::badge
          color="gray"
          x-text="response.statusText"
        ></x-filament::badge>
      </template>
    </p>

    <template x-if="response.body">
      <div class="foad-response-block">
        <div class="foad-sample-scroll foad-response-code">
          <pre class="foad-sample-code"><code
              x-bind:class="`language-${responsePrismLanguage}`"
              x-html="highlightedResponseBody"
            ></code></pre>
        </div>
      </div>
    </template>
    <template x-if="!response.body">
      <p class="fi-section-header-description">{{ __("filament-openapi-docs::ui.empty.no_content") }}</p>
    </template>
  </div>
</template>

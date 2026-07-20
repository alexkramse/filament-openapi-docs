<template x-if="hasBody">
  <div class="foad-stack foad-stack-sm">
    <div class="foad-body-toolbar">
      <h4 class="fi-section-header-heading">
        {{ __('filament-openapi-docs::ui.labels.body') }}
      </h4>
      <x-filament::button
        size="xs"
        icon="heroicon-m-document-duplicate"
        icon-position="after"
        outlined
        x-on:click="copyBody()"
      />
    </div>

    <template x-if="hasJsonBody">
      <div class="foad-json-editor">
        <pre
          class="foad-json-editor-highlight foad-sample-code"
          x-ref="bodyHighlightScroller"
          aria-hidden="true"
        ><code class="language-json" x-html="highlightedBodyText"></code></pre>
        <textarea
          class="foad-try-textarea foad-json-editor-textarea"
          x-model="bodyText"
          x-on:input.debounce.500ms="formatJsonBody(false, $event.target.value)"
          x-on:blur="formatJsonBody(true, $event.target.value)"
          x-on:scroll="syncBodyEditorScroll($event)"
          spellcheck="false"
          wrap="off"
        ></textarea>
      </div>
    </template>

    <template x-if="hasFormUrlEncodedBody">
      <textarea
        class="foad-try-textarea"
        x-bind:value="encodedFormBodyText()"
        readonly
        spellcheck="false"
      ></textarea>
    </template>

    <template x-if="!hasJsonBody && !hasFormUrlEncodedBody">
      <textarea
        class="foad-try-textarea"
        x-model="bodyText"
        spellcheck="false"
      ></textarea>
    </template>

    <template x-if="bodyJsonError">
      <p class="foad-sample-error foad-try-message" x-text="bodyJsonError"></p>
    </template>
  </div>
</template>

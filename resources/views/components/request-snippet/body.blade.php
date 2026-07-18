<template x-if="hasBody">
  <div class="foad-stack foad-stack-sm">
    <div class="foad-inline-list foad-inline-list-sm">
      <h4 class="fi-section-header-heading">
        {{ __('filament-openapi-docs::ui.labels.body') }}
      </h4>
      <template x-if="hasJsonBody">
        <x-filament::button
          color="gray"
          size="xs"
          type="button"
          x-on:click="formatJsonBody()"
          >{{ __('filament-openapi-docs::ui.actions.format_json') }}</x-filament::button
        >
      </template>
    </div>

    <textarea
      class="foad-try-textarea"
      x-model="bodyText"
      spellcheck="false"
    ></textarea>

    <template x-if="bodyJsonError">
      <p class="foad-sample-error foad-try-message" x-text="bodyJsonError"></p>
    </template>
  </div>
</template>

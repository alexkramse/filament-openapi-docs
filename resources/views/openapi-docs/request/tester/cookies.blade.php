<template x-if="hasCookieParameters">
  <div class="foad-stack foad-stack-sm">
    <div class="foad-inline-list foad-inline-list-sm">
      <h4 class="fi-section-header-heading">
        {{ __('filament-openapi-docs::ui.labels.cookies') }}
      </h4>
    </div>
    <div class="foad-send-controls foad-send-controls-grid">
      <template
        x-for="parameter in cookieParameters"
        x-bind:key="`cookie-${parameter.name}`"
      >
        <x-filament::input.wrapper>
          <x-slot name="prefix">
            <span x-text="parameter.name"></span>
          </x-slot>

          <x-filament::input
            type="text"
            x-model="parameter.value"
          />
        </x-filament::input.wrapper>
      </template>
    </div>
  </div>
</template>

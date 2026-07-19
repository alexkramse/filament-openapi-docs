<template x-if="hasAuthParameters">
  <div class="foad-stack foad-stack-sm">
    <div class="foad-inline-list foad-inline-list-sm">
      <h4 class="fi-section-header-heading">
        {{ __('filament-openapi-docs::ui.labels.security') }}
      </h4>
    </div>
    <div class="foad-send-controls foad-send-controls-grid">
      <template
        x-for="parameter in authParameters"
        x-bind:key="`${parameter.location}-${parameter.name}`"
      >
        <x-filament::input.wrapper>
          <x-slot name="prefix">
            <span x-text="parameter.label"></span>
          </x-slot>

          <x-filament::input
            type="text"
            x-model="parameter.value"
            x-bind:placeholder="parameter.placeholder"
          />
        </x-filament::input.wrapper>
      </template>
    </div>
  </div>
</template>

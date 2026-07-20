<template x-if="hasHeaderParameters">
  <div class="foad-stack foad-stack-sm">
    <div class="foad-send-controls foad-justify-content-space-between">
      <h4 class="fi-section-header-heading">
        {{ __('filament-openapi-docs::ui.labels.headers') }}
      </h4>
      <x-filament::link
        size="xs"
        icon="heroicon-m-plus"
        tag="button"
        x-show="canUseDeveloperOptions"
        x-on:click="addHeader()"
        >{{ __('filament-openapi-docs::ui.actions.add_header') }}</x-filament::link
      >
    </div>
    <div class="foad-send-controls foad-send-controls-grid">
      <template
        x-for="parameter in mediaHeaderParameters"
        x-bind:key="`media-header-${parameter.name}`"
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

      <template
        x-for="(parameter, index) in headerParameters"
        x-bind:key="`header-${index}`"
      >
        <div>
          <template x-if="!parameter.removable">
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
          <template x-if="parameter.removable">
            <div class="foad-header-row">
              <x-filament::input.wrapper>
                <x-filament::input
                  type="text"
                  x-bind:id="`header-name-${index}`"
                  x-model="parameter.name"
                  x-bind:disabled="!canUseDeveloperOptions"
                  placeholder="{{ __('filament-openapi-docs::ui.placeholders.name') }}"
                />
              </x-filament::input.wrapper>

              <x-filament::input.wrapper>
                <x-filament::input
                  type="text"
                  x-bind:id="`header-value-${index}`"
                  x-model="parameter.value"
                  placeholder="{{ __('filament-openapi-docs::ui.placeholders.value') }}"
                />
              </x-filament::input.wrapper>

              <x-filament::icon-button
                color="gray"
                icon="heroicon-m-x-mark"
                label="{{ __('filament-openapi-docs::ui.actions.remove') }}"
                type="button"
                class="foad-header-remove"
                x-show="canUseDeveloperOptions && parameter.removable"
                x-on:click="removeHeader(index)"
              />
            </div>
          </template>
        </div>
      </template>
    </div>
  </div>
</template>

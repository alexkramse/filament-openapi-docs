<template x-if="hasQueryParameters">
    <div class="foad-stack foad-stack-sm">
        <div class="foad-send-controls foad-justify-content-space-between">
            <h4 class="fi-section-header-heading">{{ __('filament-openapi-docs::ui.labels.query_parameters') }}</h4>
            <x-filament::button size="xs" type="button" x-show="developerMode" x-on:click="addQueryParameter()">{{ __('filament-openapi-docs::ui.actions.add_parameter') }}</x-filament::button>
        </div>

        <div class="foad-send-controls foad-send-controls-grid">
            <template x-for="(parameter, index) in queryParameters" x-bind:key="`query-${index}`">
                <div x-show="developerMode || ! parameter.developerOnly">
                    <template x-if="! parameter.removable">
                        <x-filament::input.wrapper>
                            <x-slot name="prefix">
                                <span x-text="parameter.name"></span>
                            </x-slot>
                            <x-filament::input type="text" x-model="parameter.value"/>
                        </x-filament::input.wrapper>
                    </template>
                    <template x-if="parameter.removable">
                        <div class="foad-header-row">
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" x-bind:id="`parameter-name-${index}`" x-model="parameter.name" placeholder="{{ __('filament-openapi-docs::ui.placeholders.name') }}"/>
                            </x-filament::input.wrapper>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" x-bind:id="`parameter-value-${index}`" x-model="parameter.value" placeholder="{{ __('filament-openapi-docs::ui.placeholders.value') }}"/>
                            </x-filament::input.wrapper>

                            <x-filament::icon-button
                                color="danger"
                                icon="heroicon-m-trash"
                                label="{{ __('filament-openapi-docs::ui.actions.remove') }}"
                                type="button"
                                class="foad-header-remove"
                                x-show="developerMode && parameter.removable"
                                x-on:click="removeQueryParameter(index)"
                            />
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</template>

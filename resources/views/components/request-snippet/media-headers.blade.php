<template x-if="hasMediaHeaderParameters">
    <div class="foad-stack foad-stack-sm">
        <div class="foad-inline-list foad-inline-list-sm">
            <h4 class="fi-section-header-heading">{{ __('filament-openapi-docs::ui.labels.media_headers') }}</h4>
        </div>

        <div class="foad-send-controls foad-send-controls-grid">
            <template x-for="parameter in mediaHeaderParameters" x-bind:key="`media-header-${parameter.name}`">
                <x-filament::input.wrapper>
                    <x-slot name="prefix">
                        <span x-text="parameter.name"></span>
                    </x-slot>

                    <x-filament::input
                        type="text"
                        x-model="parameter.value"
                        x-bind:disabled="! developerMode"
                    />
                </x-filament::input.wrapper>
            </template>
        </div>
    </div>
</template>

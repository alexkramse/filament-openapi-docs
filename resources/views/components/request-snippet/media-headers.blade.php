<template x-if="hasMediaHeaderParameters">
    <div class="foad-stack foad-stack-sm">
        <div class="foad-inline-list foad-inline-list-sm">
            <h4 class="fi-section-header-heading">Media headers</h4>
        </div>

        <div class="foad-grid">
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

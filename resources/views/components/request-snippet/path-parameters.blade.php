<template x-if="hasPathParameters">
    <div class="foad-stack foad-stack-sm">
        <div class="foad-inline-list foad-inline-list-sm">
            <h4 class="fi-section-header-heading">Path parameters</h4>
        </div>
        <div class="foad-grid">
            <template x-for="parameter in pathParameters" x-bind:key="parameter.name">
                <label class="foad-try-field">
                    <span class="foad-property-meta-label" x-text="parameter.name"></span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" x-model="parameter.value"/>
                    </x-filament::input.wrapper>
                </label>
            </template>
        </div>
    </div>
</template>

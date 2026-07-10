<template x-if="hasAuthParameters">
    <div class="foad-stack foad-stack-sm">
        <h4 class="fi-section-header-heading">Auth</h4>

        <div class="foad-grid">
            <template x-for="parameter in authParameters" x-bind:key="`${parameter.location}-${parameter.name}`">
                <label class="foad-try-field">
                    <span class="foad-property-meta-label" x-text="parameter.label"></span>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="password"
                            x-model="parameter.value"
                            x-bind:placeholder="parameter.placeholder"
                        />
                    </x-filament::input.wrapper>
                </label>
            </template>
        </div>
    </div>
</template>

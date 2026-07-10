<template x-if="hasQueryParameters">
    <div class="foad-stack foad-stack-sm">
        <div class="foad-inline-list foad-inline-list-sm">
            <h4 class="fi-section-header-heading">Query parameters</h4>
            <x-filament::button size="xs" type="button" x-show="developerMode" x-on:click="addQueryParameter()">Add parameter</x-filament::button>
        </div>

        <div class="foad-grid">
            <template x-for="(parameter, index) in queryParameters" x-bind:key="`query-${index}`">
                <div class="foad-header-row" x-show="developerMode || ! parameter.developerOnly">
                    <label class="foad-try-field">
                        <span class="foad-property-meta-label" x-text="parameter.removable ? 'Name' : parameter.name"></span>
                        <template x-if="parameter.removable">
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" x-model="parameter.name" placeholder="Name" />
                            </x-filament::input.wrapper>
                        </template>
                        <template x-if="! parameter.removable">
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" x-model="parameter.value" />
                            </x-filament::input.wrapper>
                        </template>
                    </label>

                    <template x-if="parameter.removable">
                        <label class="foad-try-field">
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" x-model="parameter.value" placeholder="Value" />
                            </x-filament::input.wrapper>
                        </label>
                    </template>

                    <x-filament::button
                        color="danger"
                        size="xs"
                        type="button"
                        class="foad-header-remove"
                        x-show="developerMode && parameter.removable"
                        x-on:click="removeQueryParameter(index)"
                    >
                        Remove
                    </x-filament::button>
                </div>
            </template>
        </div>
    </div>
</template>

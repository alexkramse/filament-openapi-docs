<div class="foad-stack foad-stack-sm">
    <div class="foad-inline-list foad-inline-list-sm">
        <h4 class="fi-section-header-heading">Headers</h4>
        <x-filament::button size="xs" type="button" x-show="developerMode" x-on:click="addHeader()">Add header</x-filament::button>
    </div>

    <template x-if="hasHeaderParameters">
        <div class="foad-stack foad-stack-sm">
            <template x-for="(parameter, index) in headerParameters" x-bind:key="`header-${index}`">
                <div class="foad-header-row">
                    <label class="foad-try-field">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" x-model="parameter.name" x-bind:disabled="parameter.disabled && ! developerMode" />
                        </x-filament::input.wrapper>
                    </label>

                    <label class="foad-try-field">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" x-model="parameter.value" x-bind:disabled="parameter.disabled && ! developerMode" />
                        </x-filament::input.wrapper>
                    </label>

                    <x-filament::button
                        color="danger"
                        size="xs"
                        type="button"
                        class="foad-header-remove"
                        x-show="developerMode && parameter.removable"
                        x-on:click="removeHeader(index)"
                    >
                        Remove
                    </x-filament::button>
                </div>
            </template>
        </div>
    </template>
</div>

<div class="foad-stack foad-stack-sm">
    <h4 class="fi-section-header-heading">Headers</h4>

    <template x-if="hasHeaderParameters">
        <template x-for="(parameter, index) in headerParameters" x-bind:key="`header-${index}`">
            <div class="foad-header-row">
                <template x-if="! parameter.removable">
                    <x-filament::input.wrapper>
                        <x-slot name="prefix">
                            <span x-text="parameter.name"></span>
                        </x-slot>
                        <x-filament::input type="text" x-model="parameter.value"
                                           x-bind:disabled="parameter.disabled && ! developerMode"/>
                    </x-filament::input.wrapper>
                </template>
                <template x-if="parameter.removable">
                    <div>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" x-bind:id="`header-name-${index}`"
                                               x-model="parameter.name" placeholder="Name"/>
                        </x-filament::input.wrapper>

                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="text" x-bind:id="`header-value-${index}`"
                                x-model="parameter.value" placeholder="Value"/>
                        </x-filament::input.wrapper>

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


{{--                <label class="foad-try-field">--}}
{{--                    <x-filament::input.wrapper>--}}
{{--                        <x-filament::input--}}
{{--                            type="text"--}}
{{--                            x-model="parameter.name"--}}
{{--                            x-bind:disabled="parameter.disabled && ! developerMode"/>--}}
{{--                    </x-filament::input.wrapper>--}}
{{--                </label>--}}

{{--                <label class="foad-try-field">--}}
{{--                    <x-filament::input.wrapper>--}}
{{--                        <x-filament::input--}}
{{--                            type="text"--}}
{{--                            x-model="parameter.value"--}}
{{--                            x-bind:disabled="parameter.disabled && ! developerMode"/>--}}
{{--                    </x-filament::input.wrapper>--}}
{{--                </label>--}}

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
    </template>
    <div class="foad-inline-list foad-inline-list-sm">
        {{--        <h4 class="fi-section-header-heading">Headers</h4>--}}
        <x-filament::button size="xs" type="button" x-show="developerMode" x-on:click="addHeader()">Add header
        </x-filament::button>
    </div>
</div>

<label class="fi-fo-field">
    <span class="fi-fo-field-label-content foad-inline-list">
        <span>{{ $parameter['name'] }}</span>

        <x-filament::badge color="gray" size="xs">
            {{ $parameter['type'] }}
        </x-filament::badge>

        @if ($parameter['required'])
            <x-filament::badge color="danger" size="xs">
                {{ __('filament-openapi-docs::ui.badges.required') }}
            </x-filament::badge>
        @endif
    </span>

    <x-filament::input.wrapper :suffix="$parameter['description']">
        <x-filament::input
            :value="$parameter['name'].': '.$parameter['type']"
            readonly
        />
    </x-filament::input.wrapper>
</label>

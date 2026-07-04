<label class="flex flex-col gap-1">
    <span class="flex items-center gap-2 text-sm font-medium text-gray-950 dark:text-white">
        <span>{{ $parameter['name'] }}</span>

        <x-filament::badge color="gray" size="xs">
            {{ $parameter['type'] }}
        </x-filament::badge>

        @if ($parameter['required'])
            <x-filament::badge color="danger" size="xs">
                Required
            </x-filament::badge>
        @endif
    </span>

    <x-filament::input.wrapper :suffix="$parameter['description']">
        <x-filament::input
            :value="$parameter['name'].': '.$parameter['type']"
            readonly
            class="font-mono text-xs"
        />
    </x-filament::input.wrapper>
</label>

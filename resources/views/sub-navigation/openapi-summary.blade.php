<div class="foad-openapi-summary">
    @if ($servers !== [])
        <div class="foad-openapi-summary-servers">
            @foreach ($servers as $server)
                <div class="foad-openapi-summary-server">
                    <x-filament::badge
                        color="info"
                        tooltip="Click to copy"
                        tabindex="0"
                        x-data="{ copied: false }"
                        x-on:click="
                            navigator.clipboard?.writeText(@js($server));
                            copied = true;
                            window.setTimeout(() => copied = false, 2000);
                        "
                        x-on:keydown.enter.prevent="$el.click()"
                        x-on:keydown.space.prevent="$el.click()"
                    >
                        {{ $server }}
                    </x-filament::badge>
                </div>
            @endforeach
        </div>
    @endif

    <div class="foad-openapi-summary-meta">
        @if (filled($info['version'] ?? null))
            <x-filament::badge color="primary">
                v{{ $info['version'] }}
            </x-filament::badge>
        @endif

        <x-filament::badge color="gray">
            {{ $endpointCount }} endpoints
        </x-filament::badge>
    </div>
</div>

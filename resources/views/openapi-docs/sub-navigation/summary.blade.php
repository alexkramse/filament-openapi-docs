<div class="foad-openapi-summary">
  <div class="foad-openapi-summary-header">
    @if ($hasVersionSelector ?? false)
      <x-filament::input.wrapper class="foad-version-selector">
        <x-filament::input.select
          x-on:change="
            const url = new URL(window.location.href);
            url.searchParams.set('version', $event.target.value);
            url.searchParams.delete('endpoint');
            window.location.assign(url.toString());
          "
        >
          @foreach ($versions as $version)
            <option value="{{ $version['key'] }}" @selected($selectedVersion === $version['key'])>
              {{ $version['label'] }}
            </option>
          @endforeach
        </x-filament::input.select>
      </x-filament::input.wrapper>
    @endif

    <div class="foad-openapi-summary-meta">
      @if (! ($hasVersionSelector ?? false) && filled($info['version'] ?? null))
        <x-filament::badge color="primary">
          v{{ $info['version'] }}
        </x-filament::badge>
      @endif

      <x-filament::badge color="gray">
        {{ __('filament-openapi-docs::ui.meta.endpoints', ['count' => $endpointCount]) }}
      </x-filament::badge>
    </div>
  </div>

  @if ($servers !== [])
    <div class="foad-openapi-summary-servers">
      @foreach ($servers as $server)
        <div class="foad-openapi-summary-server">
          <x-filament-openapi-docs::copyable-badge
            color="info"
            :text="$server"
            icon="heroicon-m-document-duplicate"
            icon-position="after"
          >
            {{ $server }}
          </x-filament-openapi-docs::copyable-badge>
        </div>
      @endforeach
    </div>
  @endif

</div>

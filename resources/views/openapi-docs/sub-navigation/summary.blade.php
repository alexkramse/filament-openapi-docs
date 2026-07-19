<div class="foad-openapi-summary">
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

  <div class="foad-openapi-summary-meta">
    @if (filled($info['version'] ?? null))
      <x-filament::badge color="primary">
        v{{ $info['version'] }}
      </x-filament::badge>
    @endif

    <x-filament::badge color="gray">
      {{ __('filament-openapi-docs::ui.meta.endpoints', ['count' => $endpointCount]) }}
    </x-filament::badge>
  </div>
</div>

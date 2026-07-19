@php
  $methodColor = \Alexkramse\FilamentOpenapiDocs\Enums\HttpMethod::color($endpoint->method);
@endphp

<x-filament::section
  :heading="$endpoint->title()"
  :description="$endpoint->description"
>
  <x-slot name="heading">
    <div class="foad-property-main">
      @if ($endpoint->deprecated)
        <x-filament::badge color="danger">
          {{ __('filament-openapi-docs::ui.badges.deprecated') }}
        </x-filament::badge>
      @endif
      {{ $endpoint->title() }}
    </div>
  </x-slot>
  <x-slot name="description">
    {{ $endpoint->description }}
    <div>
      <x-filament::badge :color="$methodColor">
        {{ $endpoint->method }}
      </x-filament::badge>
      <x-filament-openapi-docs::copyable-badge
        color="gray"
        icon="heroicon-m-document-duplicate"
        icon-position="after"
        :text="$endpoint->path"
        :tabindex="count($documentedServers)+1"
      >
        <strong>{{ $endpoint->path }}</strong>
      </x-filament-openapi-docs::copyable-badge>
      @foreach ($documentedServers as $server)
        <x-filament-openapi-docs::copyable-badge
          color="gray"
          icon="heroicon-m-document-duplicate"
          icon-position="after"
          :text="$server.$endpoint->path"
          :tabindex="$loop->index"
        >
          {{ $server }}
          <x-filament::badge size="xs">
            <strong>{{ $endpoint->path }}</strong>
          </x-filament::badge>
        </x-filament-openapi-docs::copyable-badge>
      @endforeach
    </div>
  </x-slot>
</x-filament::section>

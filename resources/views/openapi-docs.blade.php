<x-filament-panels::page>
  <div
    x-data="{
      init() {
        this.$nextTick(() => {
          const labels = Array.from(
            document.querySelectorAll(
              '.fi-page-sub-navigation-sidebar [data-group-label]',
            ),
          )
            .map((group) => group.dataset.groupLabel)
            .filter(Boolean);

          if (!labels.length || !this.$store.sidebar?.collapsedGroups) {
            return;
          }

          this.$store.sidebar.collapsedGroups = [
            ...this.$store.sidebar.collapsedGroups.filter(
              (label) => !labels.includes(label),
            ),
            ...labels.slice(1),
          ];
        });
      },
    }"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('openapi-docs', package: 'alexkramse/filament-openapi-docs'))]"
    class="foad-stack"
  >
    @if ($selectedEndpoint)
      @php
        $endpoint = $selectedEndpoint;
        $examplePresenter = app(\Alexkramse\FilamentOpenapiDocs\Services\ExamplePresenter::class);
        $schemaComponents = $components ?? [];
        $documentedServers = $servers ?? [];
        $requestData = app(\Alexkramse\FilamentOpenapiDocs\Services\RequestSnippetPresenter::class)
            ->present($endpoint, $documentedServers, $schemaComponents);
        $hasSamplePreviews = $endpoint->hasRequestBody()
            || collect($endpoint->responses)->contains(fn (array $response): bool => ($response['content'] ?? []) !== []);
      @endphp

      <section
        id="{{ $endpoint->id }}"
        class="foad-stack"
        @if ($requestData['hasRequestSamples'] || $hasSamplePreviews)
          x-load
          x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('request-snippet', 'alexkramse/filament-openapi-docs') }}"
          x-data="requestSnippet(@js($requestData))"
        @else
          x-data="{ sendMode: false, developerMode: false }"
        @endif
      >
        @include ('filament-openapi-docs::openapi-docs.info', [
            'endpoint' => $endpoint,
            'documentedServers' => $documentedServers,
        ])

        @include ('filament-openapi-docs::openapi-docs.request', [
            'endpoint' => $endpoint,
            'components' => $schemaComponents,
            'examplePresenter' => $examplePresenter,
            'requestData' => $requestData,
        ])

        @include ('filament-openapi-docs::openapi-docs.http-snippet', [
            'requestData' => $requestData,
        ])

        @include ('filament-openapi-docs::openapi-docs.response', [
            'endpoint' => $endpoint,
            'schemaComponents' => $schemaComponents,
            'examplePresenter' => $examplePresenter,
        ])
      </section>
    @endif
  </div>
</x-filament-panels::page>

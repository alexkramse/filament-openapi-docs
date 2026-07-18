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
      <section class="foad-stack">
        @include ('filament-openapi-docs::components.endpoint', ['endpoint' => $selectedEndpoint])
      </section>
    @endif
  </div>
</x-filament-panels::page>

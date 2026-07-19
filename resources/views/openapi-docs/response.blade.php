<x-filament::section
  compact
  :contained="false"
  :heading="__('filament-openapi-docs::ui.labels.responses')"
  :description="__('filament-openapi-docs::ui.meta.documented_responses', ['count' => count($endpoint->responses)])"
>
  <x-slot name="heading">
    {{ __('filament-openapi-docs::ui.labels.responses') }}
  </x-slot>
  <x-slot name="description">
    {{ __('filament-openapi-docs::ui.meta.documented_responses', ['count' => count($endpoint->responses)]) }}
  </x-slot>
</x-filament::section>

@foreach ($endpoint->responses as $status => $response)
  @include ('filament-openapi-docs::openapi-docs.response.item', [
      'status' => $status,
      'response' => $response,
      'schemaComponents' => $schemaComponents,
      'examplePresenter' => $examplePresenter,
  ])
@endforeach

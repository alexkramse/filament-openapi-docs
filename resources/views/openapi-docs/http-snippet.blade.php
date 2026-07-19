<x-filament::section
  class="foad-sample-section"
  :heading="__('filament-openapi-docs::ui.labels.request_sample')"
  collapsible
  collapsed
>
  @if (! $requestData['hasRequestSamples'])
    <p class="fi-section-header-description">{{ __('filament-openapi-docs::ui.empty.request_sample') }}</p>
  @else
    <div class="foad-inline-list foad-inline-list-end foad-inline-list-md">
      @if (count($requestData['requests']) > 1)
        <x-filament::input.wrapper>
          <x-filament::input.select
            x-model="activeRequest"
            aria-label="{{ __('filament-openapi-docs::ui.aria.request_example') }}"
          >
            @foreach ($requestData['requests'] as $request)
              <option value="{{ $request['key'] }}">
                {{ $request['label'] }}
              </option>
            @endforeach
          </x-filament::input.select>
        </x-filament::input.wrapper>
      @endif

      <x-filament::input.wrapper>
        <x-filament::input.select
          x-model="activeTarget"
          x-on:change="selectTarget()"
          aria-label="{{ __('filament-openapi-docs::ui.aria.request_sample_language') }}"
        >
          <template x-for="target in targets" x-bind:key="target.key">
            <option
              x-bind:value="target.key"
              x-bind:selected="target.key === activeTarget"
              x-text="target.label"
            ></option>
          </template>
        </x-filament::input.select>
      </x-filament::input.wrapper>

      <x-filament::input.wrapper>
        <x-filament::input.select
          x-model="activeClient"
          aria-label="{{ __('filament-openapi-docs::ui.aria.request_sample_client') }}"
        >
          <template
            x-for="client in selectedClients"
            x-bind:key="client.key"
          >
            <option
              x-bind:value="client.key"
              x-bind:selected="client.key === activeClient"
              x-text="client.label"
            ></option>
          </template>
        </x-filament::input.select>
      </x-filament::input.wrapper>

      <x-filament::button
        icon="heroicon-m-document-duplicate"
        icon-position="after"
        outlined
        x-on:click="copy()"
      />
    </div>

    <div>
      <template x-if="error">
        <p class="foad-sample-error" x-text="error"></p>
      </template>

      <div class="foad-sample-scroll">
        <pre
          class="foad-sample-code"
        ><code x-bind:class="`language-${prismLanguage}`"
               x-html="highlightedCode"></code></pre>
      </div>
    </div>
  @endif
</x-filament::section>

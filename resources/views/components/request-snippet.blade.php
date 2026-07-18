@if (! $requestData['hasRequestSamples'])
  <p class="fi-section-header-description">{{ __('filament-openapi-docs::ui.empty.request_sample') }}</p>
@else
  @php
        $copiedLabel = \Illuminate\Support\Js::from(__('filament-openapi-docs::ui.actions.copied'));
        $copyLabel = \Illuminate\Support\Js::from(__('filament-openapi-docs::ui.actions.copy'));
    @endphp

  <div class="fi-sc-component">
    <div class="fi-sc-tabs fi-contained">
      <nav class="fi-tabs fi-contained foad-sample-toolbar">
        <div class="foad-inline-list foad-inline-list-sm">
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
        </div>

        <x-filament::button
          type="button"
          x-on:click="copy()"
          x-text="copied ? {{ $copiedLabel }} : {{ $copyLabel }}"
        />
      </nav>
      <div class="fi-sc-tabs-tab fi-active">
        <div>
          <template x-if="error">
            <p class="foad-sample-error" x-text="error"></p>
          </template>

          <pre
            class="foad-sample-code"
          ><code x-bind:class="`language-${prismLanguage}`"
                                                        x-html="highlightedCode"></code></pre>
        </div>
      </div>
    </div>
  </div>
@endif

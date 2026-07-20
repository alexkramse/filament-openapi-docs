@props ([
    'label' => null,
    'contentType' => null,
    'samples' => [],
])

@php
    $sampleOptions = collect($samples ?? [])
        ->values()
        ->map(fn (array $sample, int $index): array => [
            ...$sample,
            'key' => 'sample-'.$index,
        ])
        ->all();
@endphp

@if ($sampleOptions !== [])
  <div x-data="{ activeSample: @js($sampleOptions[0]['key']) }">
    @if (count($sampleOptions) > 1)
      <select
        class="foad-sample-select"
        x-model="activeSample"
        aria-label="{{ $label ?? __('filament-openapi-docs::ui.labels.example') }}"
      >
        @foreach ($sampleOptions as $sample)
          <option value="{{ $sample['key'] }}">{{ $sample['label'] }}</option>
        @endforeach
      </select>
    @endif

    @foreach ($sampleOptions as $sample)
      <div x-show="activeSample === @js($sample['key'])">
        <div class="foad-sample-scroll">
          <pre class="foad-sample-code"><code
                x-bind:class="'language-' + samplePrismLanguage(@js($contentType ?? ''))"
                x-html="highlightSample(@js($sample['value']), @js($contentType ?? ''))"
              ></code></pre>
        </div>
      </div>
    @endforeach
  </div>
@endif

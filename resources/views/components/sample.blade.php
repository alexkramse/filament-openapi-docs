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
            <select class="foad-sample-select" x-model="activeSample" aria-label="{{ $label ?? __('filament-openapi-docs::ui.labels.example') }}">
                @foreach ($sampleOptions as $sample)
                    <option value="{{ $sample['key'] }}">{{ $sample['label'] }}</option>
                @endforeach
            </select>
        @endif

        @foreach ($sampleOptions as $sample)
            <pre
                class="foad-sample-code"
                x-show="activeSample === @js($sample['key'])"
            ><code>{{ $sample['value'] }}</code></pre>
        @endforeach
    </div>
@endif

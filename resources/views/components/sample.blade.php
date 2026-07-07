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
    <div
        class="foad-sample"
        x-data="{ activeSample: @js($sampleOptions[0]['key']) }"
    >
        <div class="foad-sample-toolbar">
            <div class="foad-inline-list foad-inline-list-sm">
                <span class="foad-property-meta-label">{{ $label ?? 'Example' }}</span>
                <x-filament::badge color="gray" size="xs">{{ $contentType }}</x-filament::badge>
            </div>

            @if (count($sampleOptions) > 1)
                <select class="foad-sample-select" x-model="activeSample" aria-label="{{ $label ?? 'Example' }}">
                    @foreach ($sampleOptions as $sample)
                        <option value="{{ $sample['key'] }}">{{ $sample['label'] }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        @foreach ($sampleOptions as $sample)
            <pre
                class="foad-sample-code"
                x-show="activeSample === @js($sample['key'])"
            ><code>{{ $sample['value'] }}</code></pre>
        @endforeach
    </div>
@endif

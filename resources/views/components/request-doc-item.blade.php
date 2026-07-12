@php
    $badges ??= [];
    $description ??= null;
    $metadata ??= [];
@endphp

<div class="foad-property-row" style="--foad-depth: 0;">
    <div class="foad-property-main">
        <span class="foad-property-title">
            <span class="foad-property-connector" aria-hidden="true"></span>
            <span class="foad-property-name">{{ $title }}</span>
        </span>

        @if ($badges !== [])
            <div class="foad-inline-list foad-inline-list-sm">
                @foreach ($badges as $badge)
                    <x-filament::badge :color="$badge['color'] ?? 'gray'" size="xs">
                        {{ $badge['label'] }}
                    </x-filament::badge>
                @endforeach
            </div>
        @endif
    </div>

    @if (filled($description))
        <p class="foad-property-description">{{ $description }}</p>
    @endif

    @foreach ($metadata as $label => $value)
        @if (filled($value))
            <div class="foad-property-meta">
                <span class="foad-property-meta-label">{{ $label }}</span>
                <span class="foad-property-meta-value">{{ $value }}</span>
            </div>
        @endif
    @endforeach
</div>

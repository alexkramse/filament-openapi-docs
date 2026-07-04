<x-filament-panels::page>
    <div @class(['-m-6' => config('filament-openapi-docs.layout.full_width', true)])>
        @include($scrambleView, [
            'spec' => $spec,
            'config' => $config,
        ])
    </div>
</x-filament-panels::page>

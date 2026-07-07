@php
    $requestSnippetData = app(\Kramarenko\FilamentOpenApiDocs\Services\RequestSnippetPresenter::class)
        ->present($endpoint, $servers ?? [], $components ?? []);
@endphp

@if (config('filament-openapi-docs.request_samples.enabled', true) && $requestSnippetData['requests'] !== [])
    <div
        class="foad-sample foad-request-snippet"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('request-snippet', 'alexkramse/filament-openapi-docs') }}"
        x-data="requestSnippet(@js($requestSnippetData))"
    >
        <div class="foad-sample-toolbar">
            <div class="foad-inline-list foad-inline-list-sm">
                <span class="foad-property-meta-label">Request sample</span>
            </div>

            <div class="foad-inline-list foad-inline-list-sm">
                @if (count($requestSnippetData['requests']) > 1)
                    <select class="foad-sample-select" x-model="activeRequest" aria-label="Request example">
                        @foreach ($requestSnippetData['requests'] as $request)
                            <option value="{{ $request['key'] }}">{{ $request['label'] }}</option>
                        @endforeach
                    </select>
                @endif

                <select class="foad-sample-select" x-model="activeTarget" x-on:change="selectTarget()" aria-label="Request sample language">
                    <template x-for="target in targets" x-bind:key="target.key">
                        <option x-bind:value="target.key" x-text="target.label"></option>
                    </template>
                </select>

                <select class="foad-sample-select" x-model="activeClient" aria-label="Request sample client">
                    <template x-for="client in selectedClients" x-bind:key="client.key">
                        <option x-bind:value="client.key" x-text="client.label"></option>
                    </template>
                </select>

                <button type="button" class="foad-sample-copy" x-on:click="copy()" x-text="copied ? 'Copied' : 'Copy'"></button>
            </div>
        </div>

        <template x-if="error">
            <p class="foad-sample-error" x-text="error"></p>
        </template>

        <pre class="foad-sample-code"><code x-bind:class="`language-${prismLanguage}`" x-html="highlightedCode"></code></pre>
    </div>
@endif

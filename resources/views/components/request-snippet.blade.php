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
        <div class="foad-try-it">
            <div class="foad-sample-toolbar">
                <div class="foad-inline-list foad-inline-list-sm">
                    <span class="foad-property-meta-label">Try it</span>
                    <template x-if="selectedRequest">
                        <span class="foad-try-it-url" x-text="currentHar.method + ' ' + currentHar.url"></span>
                    </template>
                </div>

                <button type="button" class="foad-send-request" x-on:click="sendRequest()" x-bind:disabled="sending">
                    <span x-text="sending ? 'Sending' : 'Send API request'"></span>
                </button>
            </div>

            <div class="foad-try-it-body">
                <template x-if="hasRequestControls">
                    <div class="foad-stack foad-stack-md">
                        <template x-if="hasAuthParameters">
                            <div class="foad-stack foad-stack-sm">
                                <h4 class="fi-section-header-heading">Auth</h4>

                                <div class="foad-grid">
                                    <template x-for="parameter in authParameters" x-bind:key="`${parameter.location}-${parameter.name}`">
                                        <label class="foad-try-field">
                                            <span class="foad-property-meta-label" x-text="parameter.label"></span>
                                            <input
                                                class="foad-try-input"
                                                type="password"
                                                x-model="parameter.value"
                                                x-bind:placeholder="parameter.placeholder"
                                            />
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="hasQueryParameters">
                            <div class="foad-stack foad-stack-sm">
                                <h4 class="fi-section-header-heading">Query parameters</h4>

                                <div class="foad-grid">
                                    <template x-for="parameter in queryParameters" x-bind:key="parameter.name">
                                        <label class="foad-try-field">
                                            <span class="foad-property-meta-label" x-text="parameter.name"></span>
                                            <input class="foad-try-input" type="text" x-model="parameter.value" />
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="hasBody">
                            <div class="foad-stack foad-stack-sm">
                                <div class="foad-inline-list foad-inline-list-sm">
                                    <h4 class="fi-section-header-heading">Body</h4>
                                    <template x-if="hasJsonBody">
                                        <button type="button" class="foad-sample-copy" x-on:click="formatJsonBody()">Format JSON</button>
                                    </template>
                                </div>

                                <textarea class="foad-try-textarea" x-model="bodyText" spellcheck="false"></textarea>

                                <template x-if="bodyJsonError">
                                    <p class="foad-sample-error foad-try-message" x-text="bodyJsonError"></p>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="! hasRequestControls">
                    <p class="fi-section-header-description">No request data documented.</p>
                </template>

                <template x-if="sendError">
                    <p class="foad-sample-error foad-try-message" x-text="sendError"></p>
                </template>

                <template x-if="response">
                    <div class="foad-response-preview">
                        <div class="foad-inline-list foad-inline-list-sm">
                            <span class="foad-property-meta-label">Response</span>
                            <span class="foad-response-status" x-bind:data-success="response.ok" x-text="response.status + ' ' + response.statusText"></span>
                            <template x-if="response.contentType">
                                <span class="foad-property-meta-label" x-text="response.contentType"></span>
                            </template>
                        </div>

                        <pre class="foad-sample-code foad-response-code"><code x-text="response.body"></code></pre>
                    </div>
                </template>
            </div>
        </div>

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

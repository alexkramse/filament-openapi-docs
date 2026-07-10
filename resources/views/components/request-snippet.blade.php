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

                <div class="foad-inline-list foad-inline-list-sm foad-inline-list-end">
                    <label class="foad-developer-mode fi-fo-toggle">
                        <x-filament::input.checkbox class="foad-developer-mode-input" x-model="developerMode" />
                        <span class="foad-developer-mode-switch" aria-hidden="true">
                            <span class="foad-developer-mode-knob"></span>
                        </span>
                        <span class="fi-fo-field-label-content">Developer mode</span>
                    </label>
                </div>
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
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="password"
                                                    x-model="parameter.value"
                                                    x-bind:placeholder="parameter.placeholder"
                                                />
                                            </x-filament::input.wrapper>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="foad-stack foad-stack-sm">
                            <div class="foad-inline-list foad-inline-list-sm">
                                <h4 class="fi-section-header-heading">Headers</h4>
                                <x-filament::button color="gray" size="xs" type="button" x-show="developerMode" x-on:click="addHeader()">Add header</x-filament::button>
                            </div>

                            <template x-if="hasHeaderParameters">
                                <div class="foad-stack foad-stack-sm">
                                    <template x-for="(parameter, index) in headerParameters" x-bind:key="`header-${index}`">
                                        <div class="foad-header-row">
                                            <label class="foad-try-field">
                                                <span class="foad-property-meta-label">Name</span>
                                                <x-filament::input.wrapper>
                                                    <x-filament::input type="text" x-model="parameter.name" x-bind:disabled="parameter.disabled && ! developerMode" />
                                                </x-filament::input.wrapper>
                                            </label>

                                            <label class="foad-try-field">
                                                <span class="foad-property-meta-label">Value</span>
                                                <x-filament::input.wrapper>
                                                    <x-filament::input type="text" x-model="parameter.value" x-bind:disabled="parameter.disabled && ! developerMode" />
                                                </x-filament::input.wrapper>
                                            </label>

                                            <x-filament::button
                                                color="gray"
                                                size="xs"
                                                type="button"
                                                class="foad-header-remove"
                                                x-show="developerMode && parameter.removable"
                                                x-on:click="removeHeader(index)"
                                            >
                                                Remove
                                            </x-filament::button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <template x-if="hasPathParameters">
                            <div class="foad-stack foad-stack-sm">
                                <h4 class="fi-section-header-heading">Path parameters</h4>

                                <div class="foad-grid">
                                    <template x-for="parameter in pathParameters" x-bind:key="parameter.name">
                                        <label class="foad-try-field">
                                            <span class="foad-property-meta-label" x-text="parameter.name"></span>
                                            <x-filament::input.wrapper>
                                                <x-filament::input type="text" x-model="parameter.value" />
                                            </x-filament::input.wrapper>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="hasQueryParameters">
                            <div class="foad-stack foad-stack-sm">
                                <div class="foad-inline-list foad-inline-list-sm">
                                    <h4 class="fi-section-header-heading">Query parameters</h4>
                                    <x-filament::button color="gray" size="xs" type="button" x-show="developerMode" x-on:click="addQueryParameter()">Add parameter</x-filament::button>
                                </div>

                                <div class="foad-grid">
                                    <template x-for="(parameter, index) in queryParameters" x-bind:key="`query-${index}`">
                                        <div class="foad-header-row" x-show="developerMode || ! parameter.developerOnly">
                                            <label class="foad-try-field">
                                                <span class="foad-property-meta-label" x-text="parameter.removable ? 'Name' : parameter.name"></span>
                                                <template x-if="parameter.removable">
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input type="text" x-model="parameter.name" />
                                                    </x-filament::input.wrapper>
                                                </template>
                                                <template x-if="! parameter.removable">
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input type="text" x-model="parameter.value" />
                                                    </x-filament::input.wrapper>
                                                </template>
                                            </label>

                                            <template x-if="parameter.removable">
                                                <label class="foad-try-field">
                                                    <span class="foad-property-meta-label">Value</span>
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input type="text" x-model="parameter.value" />
                                                    </x-filament::input.wrapper>
                                                </label>
                                            </template>

                                            <x-filament::button
                                                color="gray"
                                                size="xs"
                                                type="button"
                                                class="foad-header-remove"
                                                x-show="developerMode && parameter.removable"
                                                x-on:click="removeQueryParameter(index)"
                                            >
                                                Remove
                                            </x-filament::button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="hasBody">
                            <div class="foad-stack foad-stack-sm">
                                <div class="foad-inline-list foad-inline-list-sm">
                                    <h4 class="fi-section-header-heading">Body</h4>
                                    <template x-if="hasJsonBody">
                                        <x-filament::button color="gray" size="xs" type="button" x-on:click="formatJsonBody()">Format JSON</x-filament::button>
                                    </template>
                                </div>

                                <textarea class="foad-try-textarea" x-model="bodyText" spellcheck="false"></textarea>

                                <template x-if="bodyJsonError">
                                    <p class="foad-sample-error foad-try-message" x-text="bodyJsonError"></p>
                                </template>
                            </div>
                        </template>


                        <x-filament::button type="button" x-on:click="sendRequest()" x-bind:disabled="sending" x-text="sending ? 'Sending' : 'Send API request'" />
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
                    <x-filament::input.wrapper>
                        <x-filament::input.select x-model="activeRequest" aria-label="Request example">
                            @foreach ($requestSnippetData['requests'] as $request)
                                <option value="{{ $request['key'] }}">{{ $request['label'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endif

                <x-filament::input.wrapper>
                    <x-filament::input.select x-model="activeTarget" x-on:change="selectTarget()" aria-label="Request sample language">
                        <template x-for="target in targets" x-bind:key="target.key">
                            <option x-bind:value="target.key" x-text="target.label"></option>
                        </template>
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <x-filament::input.wrapper>
                    <x-filament::input.select x-model="activeClient" aria-label="Request sample client">
                        <template x-for="client in selectedClients" x-bind:key="client.key">
                            <option x-bind:value="client.key" x-text="client.label"></option>
                        </template>
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <x-filament::button color="gray" size="xs" type="button" x-on:click="copy()" x-text="copied ? 'Copied' : 'Copy'" />
            </div>
        </div>

        <template x-if="error">
            <p class="foad-sample-error" x-text="error"></p>
        </template>

        <pre class="foad-sample-code"><code x-bind:class="`language-${prismLanguage}`" x-html="highlightedCode"></code></pre>
    </div>
@endif

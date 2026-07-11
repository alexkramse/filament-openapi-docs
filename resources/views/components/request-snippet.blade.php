@php
    $requestSnippetData ??= app(\Kramarenko\FilamentOpenApiDocs\Services\RequestSnippetPresenter::class)
        ->present($endpoint, $servers ?? [], $components ?? []);
    $usesExternalRequestSnippetState ??= false;
@endphp

@if (config('filament-openapi-docs.request_samples.enabled', true) && $requestSnippetData['requests'] !== [])
    <div
        class="foad-request-snippet"
        @unless ($usesExternalRequestSnippetState)
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('request-snippet', 'alexkramse/filament-openapi-docs') }}"
            x-data="requestSnippet(@js($requestSnippetData))"
        @endunless
    >


            {{--          <div class="foad-sample-toolbar">
    {{--                <div class="foad-inline-list foad-inline-list-sm">
                             <span class="foad-property-meta-label">Try it</span>
                             <template x-if="selectedRequest">
                                 <span class="foad-try-it-url" x-text="currentHar.method + ' ' + currentHar.url"></span>
                             </template>
                         </div>
            </div>--}}

{{--                <div x-if="hasRequestControls">--}}
{{--                    <div class="foad-stack foad-stack-md">--}}
                        @include('filament-openapi-docs::components.request-snippet.auth')
                        @include('filament-openapi-docs::components.request-snippet.headers')
                        @include('filament-openapi-docs::components.request-snippet.path-parameters')
                        @include('filament-openapi-docs::components.request-snippet.query-parameters')
                        @include('filament-openapi-docs::components.request-snippet.body')

                        <x-filament::button type="button" x-on:click="sendRequest()" x-bind:disabled="sending"
                                            x-text="sending ? 'Sending' : 'Send API request'"/>
{{--                    </div>--}}
{{--                </div>--}}

{{--                <template x-if="! hasRequestControls">--}}
{{--                    <p class="fi-section-header-description">No request data documented.</p>--}}
{{--                </template>--}}

                <template x-if="sendError">
                    <p class="foad-sample-error foad-try-message" x-text="sendError"></p>
                </template>

                <template x-if="response">
                    <div class="foad-response-preview">
                        <div class="foad-inline-list foad-inline-list-sm">
                            <span class="foad-property-meta-label">Response</span>
                            <span class="foad-response-status" x-bind:data-success="response.ok"
                                  x-text="response.status + ' ' + response.statusText"></span>
                            <template x-if="response.contentType">
                                <span class="foad-property-meta-label" x-text="response.contentType"></span>
                            </template>
                        </div>

                        <pre class="foad-sample-code foad-response-code"><code x-text="response.body"></code></pre>
                    </div>
                </template>


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
                    <x-filament::input.select x-model="activeTarget" x-on:change="selectTarget()"
                                              aria-label="Request sample language">
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

                <x-filament::button color="gray" size="xs" type="button" x-on:click="copy()"
                                    x-text="copied ? 'Copied' : 'Copy'"/>
            </div>
        </div>

        <template x-if="error">
            <p class="foad-sample-error" x-text="error"></p>
        </template>

        <pre class="foad-sample-code"><code x-bind:class="`language-${prismLanguage}`" x-html="highlightedCode"></code></pre>
    </div>
@endif

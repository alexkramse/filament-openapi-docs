<div class="foad-stack foad-stack-md">
    <label class="foad-developer-mode fi-fo-toggle">
        <x-filament::input.checkbox class="foad-developer-mode-input" x-model="developerMode" />
        <span class="foad-developer-mode-switch" aria-hidden="true">
            <span class="foad-developer-mode-knob"></span>
        </span>
        <span class="fi-fo-field-label-content">Developer mode</span>
    </label>

    @include('filament-openapi-docs::components.request-snippet.auth')
    @include('filament-openapi-docs::components.request-snippet.media-headers')
    @include('filament-openapi-docs::components.request-snippet.headers')
    @include('filament-openapi-docs::components.request-snippet.path-parameters')
    @include('filament-openapi-docs::components.request-snippet.query-parameters')
    @include('filament-openapi-docs::components.request-snippet.body')

    <template x-if="! hasRequestControls">
        <p class="fi-section-header-description">No request data documented.</p>
    </template>

    <x-filament::button
        type="button"
        x-on:click="sendRequest()"
        x-bind:disabled="sending"
        x-text="sending ? 'Sending' : 'Send API request'"
    />

    <template x-if="sendError">
        <p class="foad-sample-error foad-try-message" x-text="sendError"></p>
    </template>

    <template x-if="response">
        <div class="foad-response-preview">
            <div class="foad-inline-list foad-inline-list-sm">
                <span class="foad-property-meta-label">Response</span>
                <span
                    class="foad-response-status"
                    x-bind:data-success="response.ok"
                    x-text="response.status + ' ' + response.statusText"
                ></span>
                <template x-if="response.contentType">
                    <span class="foad-property-meta-label" x-text="response.contentType"></span>
                </template>
            </div>

            <pre class="foad-sample-code foad-response-code"><code x-text="response.body"></code></pre>
        </div>
    </template>
</div>

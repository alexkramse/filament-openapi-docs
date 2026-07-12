@if ($requestData['hasRequestSamples'])
    <div class="foad-request-snippet">
        <div class="foad-sample-toolbar">
            <div class="foad-inline-list foad-inline-list-sm">
                <span class="foad-property-meta-label">Request sample</span>
            </div>

            <div class="foad-inline-list foad-inline-list-sm">
                @if (count($requestData['requests']) > 1)
                    <x-filament::input.wrapper>
                        <x-filament::input.select x-model="activeRequest" aria-label="Request example">
                            @foreach ($requestData['requests'] as $request)
                                <option value="{{ $request['key'] }}">{{ $request['label'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endif

                <x-filament::input.wrapper>
                    <x-filament::input.select
                        x-model="activeTarget"
                        x-on:change="selectTarget()"
                        aria-label="Request sample language"
                    >
                        <template x-for="target in targets" x-bind:key="target.key">
                            <option
                                x-bind:value="target.key"
                                x-bind:selected="target.key === activeTarget"
                                x-text="target.label"
                            ></option>
                        </template>
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <x-filament::input.wrapper>
                    <x-filament::input.select x-model="activeClient" aria-label="Request sample client">
                        <template x-for="client in selectedClients" x-bind:key="client.key">
                            <option
                                x-bind:value="client.key"
                                x-bind:selected="client.key === activeClient"
                                x-text="client.label"
                            ></option>
                        </template>
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <x-filament::button
                    type="button"
                    x-on:click="copy()"
                    x-text="copied ? 'Copied' : 'Copy'"
                />
            </div>
        </div>

        <template x-if="error">
            <p class="foad-sample-error" x-text="error"></p>
        </template>

        <pre class="foad-sample-code"><code x-bind:class="`language-${prismLanguage}`" x-html="highlightedCode"></code></pre>
    </div>
@endif

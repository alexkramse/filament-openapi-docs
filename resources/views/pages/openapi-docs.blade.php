<x-filament-panels::page>
    <style>
        .foad-openapi-docs-page .fi-page-sub-navigation-sidebar-ctn {
            position: sticky;
            top: 1.5rem;
            max-height: calc(100vh - 3rem);
            overflow-y: auto;

            .fi-page-sub-navigation-sidebar{
                row-gap: 0;
            }
        }

        .foad-stack {
            display: grid;
            gap: 1.5rem;
        }

        .foad-stack-md {
            gap: 1rem;
        }

        .foad-stack-sm {
            gap: .5rem;
        }

        .foad-grid {
            display: grid;
            gap: .75rem;
        }

        .foad-inline-list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }

        .foad-inline-list-sm {
            gap: .25rem;
        }

        .foad-inline-list-end {
            align-items: flex-end;
        }

        .foad-schema-row-header {
            display: grid;
            gap: .75rem;
        }

        .foad-response-block {
            display: grid;
            gap: .75rem;

            .foad-property-main{
                justify-content: start;
            }

        }

        .foad-response-block + .foad-response-block {
            border-top: 1px solid color-mix(in oklab, currentColor 12%, transparent);
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .foad-schema-tree {
            display: grid;
            gap: 0rem;
        }

        .foad-property-row {
            --foad-tree-indent: 2rem;
            border-inline-start: 2px solid color-mix(in oklab, currentColor 16%, transparent);
            display: grid;
            gap: .375rem;
            margin-inline-start: calc(var(--foad-depth, 0) * var(--foad-tree-indent));
            padding: .5rem 0 .5rem calc(var(--foad-tree-indent));
        }

        .foad-property-summary {
            cursor: pointer;
            display: grid;
            gap: .375rem;
            list-style: none;
        }

        .foad-property-summary::-webkit-details-marker {
            display: none;
        }

        .foad-property-main {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
        }

        .foad-property-title {
            align-items: center;
            display: inline-flex;
            min-width: 0;
        }

        .foad-property-connector {
            background: color-mix(in oklab, currentColor 28%, transparent);
            display: inline-block;
            height: 1px;
            margin-inline-start: calc((var(--foad-tree-indent) * -1) - 2px);
            width: calc(var(--foad-tree-indent) - .2rem);
        }

        .foad-property-summary {
            .foad-property-connector {
                width: calc(var(--foad-tree-indent) - 1.2rem);
                margin-inline-start: calc((var(--foad-tree-indent) * -1));
            }

            .foad-property-name {
                margin-left: 0;
            }
        }

        .foad-property-toggle {
            align-items: center;
            display: inline-flex;
            /*height: 1rem;*/
            justify-content: center;
            /*margin-inline: -.0625rem .25rem;*/
            opacity: .55;
            /*width: 1rem;*/
        }

        .foad-property-toggle-icon {
            /*height: 1rem;*/
            /*width: 1rem;*/
        }

        .foad-property-toggle-icon-open {
            display: none;
        }

        .foad-property-row-collapsible[open] > .foad-property-summary .foad-property-toggle-icon-collapsed {
            display: none;
        }

        .foad-property-row-collapsible[open] > .foad-property-summary .foad-property-toggle-icon-open {
            display: block;
        }

        .foad-property-name {
            color: var(--gray-950);
            font-size: .875rem;
            font-weight: 600;
            margin-left: .375rem;
        }

        .dark .foad-property-name {
            color: white;
        }

        .foad-property-description {
            color: var(--gray-500);
            font-size: .8125rem;
        }

        .dark .foad-property-description {
            color: var(--gray-400);
        }

        .foad-property-meta {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: .375rem;
        }

        .foad-property-meta-label {
            color: var(--gray-500);
            font-size: .75rem;
            font-weight: 600;
        }

        .dark .foad-property-meta-label {
            color: var(--gray-400);
        }

        .foad-sample {
            border: 1px solid color-mix(in oklab, currentColor 12%, transparent);
            border-radius: .5rem;
            overflow: hidden;
        }

        .foad-sample-toolbar {
            align-items: center;
            background: color-mix(in oklab, currentColor 4%, transparent);
            border-bottom: 1px solid color-mix(in oklab, currentColor 12%, transparent);
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: space-between;
            padding: .5rem .75rem;
        }

        .foad-sample-select {
            background: transparent;
            border: 0;
            color: var(--gray-950);
            font-size: .8125rem;
            font-weight: 600;
            max-width: 100%;
        }

        .dark .foad-sample-select {
            color: white;
        }

        .foad-sample-copy {
            background: transparent;
            border: 0;
            color: var(--primary-600);
            cursor: pointer;
            font-size: .8125rem;
            font-weight: 600;
            padding: .125rem .25rem;
        }

        .dark .foad-sample-copy {
            color: var(--primary-400);
        }

        .foad-sample-error {
            color: var(--danger-600);
            font-size: .8125rem;
            margin: 0;
            padding: .75rem .75rem 0;
        }

        .dark .foad-sample-error {
            color: var(--danger-400);
        }

        .foad-sample-code {
            color: var(--gray-950);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: .8125rem;
            line-height: 1.5;
            margin: 0;
            overflow-x: auto;
            padding: .75rem;
            white-space: pre;
        }

        .dark .foad-sample-code {
            color: white;
        }

        .foad-sample-code .token.comment,
        .foad-sample-code .token.prolog,
        .foad-sample-code .token.doctype,
        .foad-sample-code .token.cdata {
            color: var(--gray-500);
        }

        .foad-sample-code .token.punctuation {
            color: var(--gray-700);
        }

        .foad-sample-code .token.property,
        .foad-sample-code .token.tag,
        .foad-sample-code .token.constant,
        .foad-sample-code .token.symbol,
        .foad-sample-code .token.deleted {
            color: var(--danger-600);
        }

        .foad-sample-code .token.boolean,
        .foad-sample-code .token.number {
            color: var(--warning-600);
        }

        .foad-sample-code .token.selector,
        .foad-sample-code .token.attr-name,
        .foad-sample-code .token.string,
        .foad-sample-code .token.char,
        .foad-sample-code .token.builtin,
        .foad-sample-code .token.inserted {
            color: var(--success-600);
        }

        .foad-sample-code .token.operator,
        .foad-sample-code .token.entity,
        .foad-sample-code .token.url,
        .foad-sample-code .language-css .token.string,
        .foad-sample-code .style .token.string {
            color: var(--info-600);
        }

        .foad-sample-code .token.atrule,
        .foad-sample-code .token.attr-value,
        .foad-sample-code .token.keyword {
            color: var(--primary-600);
        }

        .foad-sample-code .token.function,
        .foad-sample-code .token.class-name {
            color: var(--warning-700);
        }

        .foad-sample-code .token.regex,
        .foad-sample-code .token.important,
        .foad-sample-code .token.variable {
            color: var(--danger-500);
        }

        .dark .foad-sample-code .token.punctuation {
            color: var(--gray-300);
        }

        .dark .foad-sample-code .token.property,
        .dark .foad-sample-code .token.tag,
        .dark .foad-sample-code .token.constant,
        .dark .foad-sample-code .token.symbol,
        .dark .foad-sample-code .token.deleted {
            color: var(--danger-400);
        }

        .dark .foad-sample-code .token.boolean,
        .dark .foad-sample-code .token.number,
        .dark .foad-sample-code .token.function,
        .dark .foad-sample-code .token.class-name {
            color: var(--warning-400);
        }

        .dark .foad-sample-code .token.selector,
        .dark .foad-sample-code .token.attr-name,
        .dark .foad-sample-code .token.string,
        .dark .foad-sample-code .token.char,
        .dark .foad-sample-code .token.builtin,
        .dark .foad-sample-code .token.inserted {
            color: var(--success-400);
        }

        .dark .foad-sample-code .token.operator,
        .dark .foad-sample-code .token.entity,
        .dark .foad-sample-code .token.url,
        .dark .foad-sample-code .language-css .token.string,
        .dark .foad-sample-code .style .token.string {
            color: var(--info-400);
        }

        .dark .foad-sample-code .token.atrule,
        .dark .foad-sample-code .token.attr-value,
        .dark .foad-sample-code .token.keyword {
            color: var(--primary-400);
        }

        .dark .foad-sample-code .token.regex,
        .dark .foad-sample-code .token.important,
        .dark .foad-sample-code .token.variable {
            color: var(--danger-300);
        }

        @media (min-width: 48rem) {
            .foad-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .foad-schema-row-header {
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: start;
            }

            .foad-inline-list-end {
                justify-content: flex-end;
            }
        }
    </style>
    <div class="foad-stack">
        <x-filament::section
            :heading="$info['title'] ?? 'API Documentation'"
            :description="$info['description'] ?? null"
        >
            <x-slot name="heading">
                @if ($servers !== [])
                    @foreach ($servers as $server)
                        <x-filament::badge color="gray">
                            {{ $server }}
                        </x-filament::badge>
                    @endforeach
                @endif

                @if (filled($info['version'] ?? null))
                    <x-filament::badge color="gray">
                        v{{ $info['version'] }}
                    </x-filament::badge>
                @endif
                <x-filament::badge color="primary">
                    {{ $endpointCount }} endpoints
                </x-filament::badge>
                <x-filament::badge color="gray">
                    {{ count($endpoints) }} groups
                </x-filament::badge>
            </x-slot>

        </x-filament::section>

        @if ($selectedEndpoint)
            <section class="foad-stack">
                @include('filament-openapi-docs::components.endpoint', ['endpoint' => $selectedEndpoint])
            </section>
        @endif
    </div>
</x-filament-panels::page>

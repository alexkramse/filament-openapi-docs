import { HTTPSnippet } from '@readme/httpsnippet';
import { targets } from '@readme/httpsnippet/targets';
import Prism from 'prismjs';
import 'prismjs/components/prism-bash';
import 'prismjs/components/prism-c';
import 'prismjs/components/prism-clojure';
import 'prismjs/components/prism-csharp';
import 'prismjs/components/prism-go';
import 'prismjs/components/prism-http';
import 'prismjs/components/prism-java';
import 'prismjs/components/prism-json';
import 'prismjs/components/prism-kotlin';
import 'prismjs/components/prism-markup-templating';
import 'prismjs/components/prism-objectivec';
import 'prismjs/components/prism-ocaml';
import 'prismjs/components/prism-php';
import 'prismjs/components/prism-powershell';
import 'prismjs/components/prism-python';
import 'prismjs/components/prism-r';
import 'prismjs/components/prism-ruby';
import 'prismjs/components/prism-crystal';
import 'prismjs/components/prism-rust';
import 'prismjs/components/prism-swift';

ensureBufferByteLength();

const prismLanguagesByTarget = {
    c: 'c',
    clojure: 'clojure',
    crystal: 'crystal',
    csharp: 'csharp',
    go: 'go',
    http: 'http',
    java: 'java',
    javascript: 'javascript',
    json: 'json',
    kotlin: 'kotlin',
    node: 'javascript',
    objc: 'objectivec',
    ocaml: 'ocaml',
    php: 'php',
    powershell: 'powershell',
    python: 'python',
    r: 'r',
    ruby: 'ruby',
    rust: 'rust',
    shell: 'bash',
    swift: 'swift',
};

const targetOptions = Object.values(targets)
    .map((target) => ({
        key: target.info.key,
        label: target.info.title,
        defaultClient: target.info.default,
        clients: Object.values(target.clientsById)
            .map((client) => ({
                key: client.info.key,
                label: client.info.title,
            }))
            .sort((first, second) => first.label.localeCompare(second.label)),
    }))
    .sort((first, second) => first.label.localeCompare(second.label));

export default function requestSnippet(config) {
    return {
        activeRequest: config.requests[0]?.key ?? null,
        activeTarget: targetOptions.find((target) => target.key === 'shell')?.key ?? targetOptions[0]?.key ?? null,
        activeClient: null,
        copied: false,
        developerMode: false,
        error: null,
        bodyText: '',
        bodyJsonError: null,
        pathParameters: [],
        queryParameters: [],
        headerParameters: [],
        authParameters: [],
        response: null,
        sendError: null,
        sending: false,
        requests: config.requests ?? [],
        targets: targetOptions,

        init() {
            this.activeClient = this.selectedTarget?.defaultClient ?? this.selectedTarget?.clients[0]?.key ?? null;
            this.resetRequestState();

            this.$watch('activeRequest', () => {
                this.resetRequestState();
            });

            this.$watch('developerMode', (enabled) => {
                if (! enabled) {
                    this.resetDeveloperModeState();
                }
            });
        },

        get selectedRequest() {
            return this.requests.find((request) => request.key === this.activeRequest) ?? this.requests[0] ?? null;
        },

        get selectedTarget() {
            return this.targets.find((target) => target.key === this.activeTarget) ?? this.targets[0] ?? null;
        },

        get selectedClients() {
            return this.selectedTarget?.clients ?? [];
        },

        get currentHar() {
            return this.buildHarRequest(true);
        },

        get hasQueryParameters() {
            return this.developerMode || this.queryParameters.length > 0;
        },

        get hasPathParameters() {
            return this.pathParameters.length > 0;
        },

        get hasAuthParameters() {
            return this.authParameters.length > 0;
        },

        get hasHeaderParameters() {
            return this.headerParameters.length > 0;
        },

        get hasBody() {
            return Boolean(this.selectedRequest?.har?.postData);
        },

        get hasJsonBody() {
            return this.selectedRequest?.har?.postData?.mimeType === 'application/json';
        },

        get hasRequestControls() {
            return this.developerMode || this.hasPathParameters || this.hasQueryParameters || this.hasHeaderParameters || this.hasAuthParameters || this.hasBody;
        },

        get code() {
            this.error = null;

            if (! this.selectedRequest || ! this.activeTarget) {
                return '';
            }

            try {
                const snippet = new HTTPSnippet(this.currentHar, {
                    harIsAlreadyEncoded: true,
                });
                const generated = snippet.convert(this.activeTarget, this.activeClient, {
                    indent: '  ',
                });

                return Array.isArray(generated) ? generated.join('\n\n') : generated;
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'Unable to generate this request sample.';

                return '';
            }
        },

        get prismLanguage() {
            return prismLanguagesByTarget[this.activeTarget] ?? 'none';
        },

        get highlightedCode() {
            const code = this.code;
            const grammar = Prism.languages[this.prismLanguage];

            if (! grammar) {
                return escapeHtml(code);
            }

            return Prism.highlight(code, grammar, this.prismLanguage);
        },

        selectTarget() {
            this.activeClient = this.selectedTarget?.defaultClient ?? this.selectedClients[0]?.key ?? null;
        },

        addHeader() {
            this.headerParameters.push({
                name: '',
                value: '',
                disabled: false,
                removable: true,
            });
        },

        removeHeader(index) {
            this.headerParameters.splice(index, 1);
        },

        addQueryParameter() {
            this.queryParameters.push({
                name: '',
                value: '',
                developerOnly: true,
                removable: true,
            });
        },

        removeQueryParameter(index) {
            this.queryParameters.splice(index, 1);
        },

        resetDeveloperModeState() {
            this.headerParameters = this.headerParameters.filter((parameter) => ! parameter.removable);
            this.queryParameters = this.queryParameters.filter((parameter) => ! parameter.developerOnly);
        },

        resetRequestState() {
            const har = this.selectedRequest?.har;

            this.response = null;
            this.sendError = null;
            this.bodyJsonError = null;

            if (! har) {
                this.pathParameters = [];
                this.queryParameters = [];
                this.headerParameters = [];
                this.authParameters = [];
                this.bodyText = '';

                return;
            }

            const auth = collectAuthParameters(har);
            const authQueryNames = auth
                .filter((parameter) => parameter.location === 'query')
                .map((parameter) => parameter.name);

            this.authParameters = auth;
            this.headerParameters = collectHeaderParameters(har, auth);
            this.pathParameters = (this.selectedRequest?.pathParameters ?? []).map((parameter) => ({
                name: parameter.name,
                value: parameter.value ?? '',
            }));
            this.queryParameters = (har.queryString ?? [])
                .filter((parameter) => ! authQueryNames.includes(parameter.name))
                .map((parameter) => ({
                    name: parameter.name,
                    value: parameter.value ?? '',
                    developerOnly: false,
                    removable: false,
                }));
            this.bodyText = har.postData?.text ?? '';
        },

        buildHarRequest(includePlaceholders = true) {
            const har = structuredCloneSafe(this.selectedRequest?.har ?? {});
            const queryString = this.queryParameters
                .filter((parameter) => this.developerMode || ! parameter.developerOnly)
                .filter((parameter) => parameter.name && String(parameter.value).length > 0)
                .map((parameter) => ({
                    name: parameter.name,
                    value: String(parameter.value),
                }));

            for (const parameter of this.authParameters.filter((item) => item.location === 'query')) {
                const value = parameter.value || (includePlaceholders ? parameter.placeholder : '');

                if (value) {
                    queryString.push({
                        name: parameter.name,
                        value,
                    });
                }
            }

            har.queryString = queryString;
            har.url = buildUrlWithQueryString(
                buildUrlWithPathParameters(this.selectedRequest?.urlTemplate ?? har.url, this.pathParameters),
                queryString,
            );

            const authHeaderNames = this.authParameters
                .filter((parameter) => parameter.location === 'header')
                .map((parameter) => parameter.name.toLowerCase());
            const editableHeaderNames = this.headerParameters
                .map((parameter) => parameter.name.toLowerCase())
                .filter((name) => name.length > 0);

            const headers = (har.headers ?? [])
                .filter((header) => ! authHeaderNames.includes(header.name.toLowerCase()))
                .filter((header) => ! editableHeaderNames.includes(header.name.toLowerCase()))
                .map((header) => ({
                    name: header.name,
                    value: header.value ?? '',
                }));

            for (const parameter of this.headerParameters) {
                if (parameter.name && String(parameter.value).length > 0) {
                    headers.push({
                        name: parameter.name,
                        value: String(parameter.value),
                    });
                }
            }

            for (const parameter of this.authParameters.filter((item) => item.location === 'header')) {
                const authValue = parameter.value || (includePlaceholders ? parameter.placeholder : '');

                if (authValue) {
                    headers.push({
                        name: parameter.name,
                        value: `${parameter.prefix ?? ''}${authValue}`,
                    });
                }
            }

            har.headers = headers;

            if (har.postData) {
                har.postData = {
                    ...har.postData,
                    text: this.bodyText,
                };
            }

            return har;
        },

        async sendRequest() {
            this.response = null;
            this.sendError = null;
            this.bodyJsonError = null;

            if (this.hasJsonBody && this.bodyText.trim() !== '') {
                try {
                    JSON.parse(this.bodyText);
                } catch (error) {
                    this.bodyJsonError = 'Body must be valid JSON before sending.';

                    return;
                }
            }

            const har = this.buildHarRequest(false);
            const headers = Object.fromEntries(
                (har.headers ?? [])
                    .filter((header) => header.value && ! isPlaceholderValue(header.value))
                    .map((header) => [header.name, header.value]),
            );
            const method = (har.method ?? 'GET').toUpperCase();

            this.sending = true;

            try {
                const response = await fetch(har.url, {
                    method,
                    headers,
                    credentials: 'same-origin',
                    body: ['GET', 'HEAD'].includes(method) ? undefined : har.postData?.text,
                });
                const contentType = response.headers.get('Content-Type') ?? '';
                const body = await response.text();

                this.response = {
                    ok: response.ok,
                    status: response.status,
                    statusText: response.statusText,
                    contentType,
                    body: formatResponseBody(body, contentType),
                };
            } catch (error) {
                this.sendError = error instanceof Error ? error.message : 'Unable to send this request.';
            } finally {
                this.sending = false;
            }
        },

        formatJsonBody() {
            this.bodyJsonError = null;

            if (! this.bodyText.trim()) {
                return;
            }

            try {
                this.bodyText = JSON.stringify(JSON.parse(this.bodyText), null, 2);
            } catch (error) {
                this.bodyJsonError = 'Body must be valid JSON before formatting.';
            }
        },

        async copy() {
            const code = this.code;

            if (! code || ! navigator.clipboard) {
                return;
            }

            await navigator.clipboard.writeText(code);
            this.copied = true;
            window.setTimeout(() => {
                this.copied = false;
            }, 2000);
        },
    };
};

function collectAuthParameters(har) {
    const auth = [];

    for (const header of har.headers ?? []) {
        const value = header.value ?? '';

        if (header.name.toLowerCase() === 'authorization') {
            if (value.toLowerCase().startsWith('bearer ')) {
                auth.push({
                    location: 'header',
                    name: header.name,
                    label: 'Bearer token',
                    prefix: 'Bearer ',
                    placeholder: '<token>',
                    value: placeholderToEmpty(value.slice(7)),
                });

                continue;
            }

            if (value.toLowerCase().startsWith('basic ')) {
                auth.push({
                    location: 'header',
                    name: header.name,
                    label: 'Basic credentials',
                    prefix: 'Basic ',
                    placeholder: '<credentials>',
                    value: placeholderToEmpty(value.slice(6)),
                });

                continue;
            }

            auth.push({
                location: 'header',
                name: header.name,
                label: 'Authorization',
                prefix: '',
                placeholder: '<credentials>',
                value: placeholderToEmpty(value),
            });

            continue;
        }

        if (value === '<api-key>') {
            auth.push({
                location: 'header',
                name: header.name,
                label: header.name,
                prefix: '',
                placeholder: '<api-key>',
                value: '',
            });
        }
    }

    for (const parameter of har.queryString ?? []) {
        if (parameter.value === '<api-key>') {
            auth.push({
                location: 'query',
                name: parameter.name,
                label: parameter.name,
                prefix: '',
                placeholder: '<api-key>',
                value: '',
            });
        }
    }

    return auth;
}

function collectHeaderParameters(har, authParameters) {
    const authHeaderNames = authParameters
        .filter((parameter) => parameter.location === 'header')
        .map((parameter) => parameter.name.toLowerCase());

    return (har.headers ?? [])
        .filter((header) => ! authHeaderNames.includes(header.name.toLowerCase()))
        .map((header) => ({
            name: header.name,
            value: header.value ?? '',
            disabled: isDefaultHeader(header.name),
            removable: false,
        }));
}

function isDefaultHeader(name) {
    return ['accept', 'content-type'].includes(name.toLowerCase());
}

function buildUrlWithQueryString(url, queryString) {
    const parsedUrl = new URL(url, window.location.origin);
    const searchParameters = new URLSearchParams();

    for (const parameter of queryString) {
        searchParameters.append(parameter.name, parameter.value);
    }

    parsedUrl.search = searchParameters.toString();

    return parsedUrl.href;
}

function buildUrlWithPathParameters(url, pathParameters) {
    return pathParameters.reduce(
        (currentUrl, parameter) => currentUrl.replace(
            new RegExp(`\\{${escapeRegExp(parameter.name)}\\}`, 'g'),
            encodeURIComponent(String(parameter.value ?? '')),
        ),
        url,
    );
}

function escapeRegExp(value) {
    return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function placeholderToEmpty(value) {
    return isPlaceholderValue(value) ? '' : value;
}

function isPlaceholderValue(value) {
    return /^<[^>]+>$/.test(String(value).trim());
}

function formatResponseBody(body, contentType) {
    if (! contentType.includes('json') || ! body) {
        return body;
    }

    try {
        return JSON.stringify(JSON.parse(body), null, 2);
    } catch (error) {
        return body;
    }
}

function structuredCloneSafe(value) {
    return JSON.parse(JSON.stringify(value));
}

function escapeHtml(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function ensureBufferByteLength() {
    if (globalThis.Buffer?.byteLength) {
        return;
    }

    globalThis.Buffer = {
        byteLength(value, encoding = 'utf8') {
            const string = String(value);

            if (['ascii', 'binary', 'latin1'].includes(String(encoding).toLowerCase())) {
                return string.length;
            }

            return new TextEncoder().encode(string).length;
        },
    };
}

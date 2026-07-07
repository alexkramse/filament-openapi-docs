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
        error: null,
        requests: config.requests ?? [],
        targets: targetOptions,

        init() {
            this.activeClient = this.selectedTarget?.defaultClient ?? this.selectedTarget?.clients[0]?.key ?? null;
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

        get code() {
            this.error = null;

            if (! this.selectedRequest || ! this.activeTarget) {
                return '';
            }

            try {
                const snippet = new HTTPSnippet(this.selectedRequest.har, {
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

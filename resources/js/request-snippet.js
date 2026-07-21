import { HTTPSnippet } from "@readme/httpsnippet";
import { targets } from "@readme/httpsnippet/targets";
import Prism from "prismjs";
import "prismjs/components/prism-bash";
import "prismjs/components/prism-c";
import "prismjs/components/prism-clojure";
import "prismjs/components/prism-csharp";
import "prismjs/components/prism-go";
import "prismjs/components/prism-http";
import "prismjs/components/prism-java";
import "prismjs/components/prism-json";
import "prismjs/components/prism-kotlin";
import "prismjs/components/prism-markup-templating";
import "prismjs/components/prism-objectivec";
import "prismjs/components/prism-ocaml";
import "prismjs/components/prism-php";
import "prismjs/components/prism-powershell";
import "prismjs/components/prism-python";
import "prismjs/components/prism-r";
import "prismjs/components/prism-ruby";
import "prismjs/components/prism-crystal";
import "prismjs/components/prism-rust";
import "prismjs/components/prism-swift";

ensureBufferByteLength();

const prismLanguagesByTarget = {
  c: "c",
  clojure: "clojure",
  crystal: "crystal",
  csharp: "csharp",
  go: "go",
  http: "http",
  java: "java",
  javascript: "javascript",
  json: "json",
  kotlin: "kotlin",
  node: "javascript",
  objc: "objectivec",
  ocaml: "ocaml",
  php: "php",
  powershell: "powershell",
  python: "python",
  r: "r",
  ruby: "ruby",
  rust: "rust",
  shell: "bash",
  swift: "swift",
};

const defaultTargetKey = "shell";
const defaultClientKey = "curl";

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
    activeTarget:
      targetOptions.find((target) => target.key === defaultTargetKey)?.key ??
      targetOptions[0]?.key ??
      null,
    activeClient: null,
    sendMode: false,
    developerMode: false,
    hasDeveloperOptions: Boolean(config.hasDeveloperOptions ?? false),
    copied: false,
    copyTimeout: null,
    error: null,
    bodyText: "",
    bodyJsonError: null,
    pathParameters: [],
    queryParameters: [],
    formParameters: [],
    cookieParameters: [],
    headerParameters: [],
    mediaHeaderParameters: [],
    authParameters: [],
    response: null,
    sendError: null,
    sending: false,
    messages: config.messages ?? {},
    requests: config.requests ?? [],
    targets: targetOptions,

    init() {
      this.activeClient = this.defaultClientForSelectedTarget();
      this.resetRequestState();

      this.$watch("activeRequest", () => {
        this.resetRequestState();
      });

      this.$watch("developerMode", (enabled) => {
        if (!enabled) {
          this.resetDeveloperModeState();
        }
      });
    },

    get selectedRequest() {
      return (
        this.requests.find((request) => request.key === this.activeRequest) ??
        this.requests[0] ??
        null
      );
    },

    get selectedTarget() {
      return (
        this.targets.find((target) => target.key === this.activeTarget) ??
        this.targets[0] ??
        null
      );
    },

    get selectedClients() {
      return this.selectedTarget?.clients ?? [];
    },

    get currentHar() {
      return this.applyRuntimeEditsToHar(true);
    },

    get canUseDeveloperOptions() {
      return this.hasDeveloperOptions && this.developerMode;
    },

    get hasQueryParameters() {
      return this.canUseDeveloperOptions || this.queryParameters.length > 0;
    },

    get hasCookieParameters() {
      return this.cookieParameters.length > 0;
    },

    get hasPathParameters() {
      return this.pathParameters.length > 0;
    },

    get hasAuthParameters() {
      return this.authParameters.length > 0;
    },

    get hasMediaHeaderParameters() {
      return this.mediaHeaderParameters.length > 0;
    },

    get hasHeaderParameters() {
      return (
        this.canUseDeveloperOptions ||
        this.headerParameters.length > 0 ||
        this.mediaHeaderParameters.length > 0
      );
    },

    get hasBody() {
      return (
        Boolean(this.selectedRequest?.har?.postData) &&
        !this.hasMultipartFormDataBody
      );
    },

    get hasJsonBody() {
      return (
        this.selectedRequest?.har?.postData?.mimeType === "application/json"
      );
    },

    get hasFormUrlEncodedBody() {
      return (
        normalizeContentType(this.selectedRequest?.har?.postData?.mimeType) ===
        "application/x-www-form-urlencoded"
      );
    },

    get hasMultipartFormDataBody() {
      return (
        normalizeContentType(this.selectedRequest?.har?.postData?.mimeType) ===
        "multipart/form-data"
      );
    },

    get hasFormRequestBody() {
      return this.hasFormUrlEncodedBody || this.hasMultipartFormDataBody;
    },

    get hasRequestControls() {
      return (
        this.canUseDeveloperOptions ||
        this.hasPathParameters ||
        this.hasCookieParameters ||
        this.hasQueryParameters ||
        this.hasHeaderParameters ||
        this.hasAuthParameters ||
        this.hasFormRequestBody ||
        this.hasBody
      );
    },

    get code() {
      this.error = null;

      if (!this.selectedRequest || !this.activeTarget) {
        return "";
      }

      try {
        const snippet = new HTTPSnippet(this.currentHar, {
          harIsAlreadyEncoded: true,
        });
        const generated = snippet.convert(
          this.activeTarget,
          this.activeClient,
          {
            indent: "  ",
          },
        );

        return Array.isArray(generated) ? generated.join("\n\n") : generated;
      } catch (error) {
        this.error =
          error instanceof Error
            ? error.message
            : this.message(
                "unableToGenerateRequestSample",
                "Unable to generate this request sample.",
              );

        return "";
      }
    },

    get prismLanguage() {
      return prismLanguagesByTarget[this.activeTarget] ?? "none";
    },

    get highlightedCode() {
      const code = this.code;
      const grammar = Prism.languages[this.prismLanguage];

      if (!grammar) {
        return escapeHtml(code);
      }

      return Prism.highlight(code, grammar, this.prismLanguage);
    },

    get highlightedBodyText() {
      const grammar = Prism.languages.json;
      const code = this.bodyText || " ";
      const visibleCode = code.endsWith("\n") ? `${code} ` : code;

      if (!grammar) {
        return escapeHtml(visibleCode);
      }

      return Prism.highlight(visibleCode, grammar, "json");
    },

    samplePrismLanguage(contentType) {
      const normalizedContentType = String(contentType ?? "").toLowerCase();

      if (normalizedContentType.includes("json")) {
        return "json";
      }

      if (
        normalizedContentType.includes("xml") ||
        normalizedContentType.includes("html") ||
        normalizedContentType.includes("svg")
      ) {
        return "markup";
      }

      return "none";
    },

    highlightSample(value, contentType) {
      const code = String(value ?? "");
      const language = this.samplePrismLanguage(contentType);
      const grammar = Prism.languages[language];

      if (!grammar) {
        return escapeHtml(code);
      }

      return Prism.highlight(code, grammar, language);
    },

    get responsePrismLanguage() {
      const contentType = this.response?.contentType ?? "";

      if (contentType.includes("json")) {
        return "json";
      }

      if (
        contentType.includes("xml") ||
        contentType.includes("html") ||
        contentType.includes("svg")
      ) {
        return "markup";
      }

      return "none";
    },

    get highlightedResponseBody() {
      const body = this.response?.body ?? "";
      const grammar = Prism.languages[this.responsePrismLanguage];

      if (!grammar) {
        return escapeHtml(body);
      }

      return Prism.highlight(body, grammar, this.responsePrismLanguage);
    },

    syncBodyEditorScroll(event) {
      const highlightScroller = this.$refs.bodyHighlightScroller;

      if (!highlightScroller) {
        return;
      }

      highlightScroller.scrollTop = event.target.scrollTop;
      highlightScroller.scrollLeft = event.target.scrollLeft;
    },

    selectTarget() {
      this.activeClient = this.defaultClientForSelectedTarget();
    },

    defaultClientForSelectedTarget() {
      if (
        this.selectedTarget?.key === defaultTargetKey &&
        this.selectedClients.some((client) => client.key === defaultClientKey)
      ) {
        return defaultClientKey;
      }

      return (
        this.selectedTarget?.defaultClient ??
        this.selectedClients[0]?.key ??
        null
      );
    },

    addHeader() {
      if (!this.canUseDeveloperOptions) {
        return;
      }

      this.headerParameters.push({
        name: "",
        value: "",
        disabled: false,
        removable: true,
      });
    },

    removeHeader(index) {
      this.headerParameters.splice(index, 1);
    },

    addQueryParameter() {
      if (!this.canUseDeveloperOptions) {
        return;
      }

      this.queryParameters.push({
        name: "",
        value: "",
        developerOnly: true,
        removable: true,
      });
    },

    removeQueryParameter(index) {
      this.queryParameters.splice(index, 1);
    },

    addFormParameter() {
      if (!this.canUseDeveloperOptions || !this.hasFormRequestBody) {
        return;
      }

      this.formParameters.push({
        name: "",
        value: "",
        type: "text",
        developerOnly: true,
        removable: true,
      });
    },

    removeFormParameter(index) {
      this.formParameters.splice(index, 1);
    },

    setFormParameterFiles(index, files) {
      if (!this.formParameters[index]) {
        return;
      }

      this.formParameters[index].files = Array.from(files ?? []);
      this.formParameters[index].value = this.formParameters[index].files
        .map((file) => file.name)
        .join(", ");
    },

    resetDeveloperModeState() {
      this.headerParameters = this.headerParameters.filter(
        (parameter) => !parameter.removable,
      );
      this.queryParameters = this.queryParameters.filter(
        (parameter) => !parameter.developerOnly,
      );
      this.formParameters = this.formParameters.filter(
        (parameter) => !parameter.developerOnly,
      );
      this.mediaHeaderParameters = cloneParameters(
        this.selectedRequest?.mediaHeaderParameters ?? [],
      );
    },

    resetRequestState() {
      this.response = null;
      this.sendError = null;
      this.bodyJsonError = null;

      if (!this.selectedRequest) {
        this.pathParameters = [];
        this.queryParameters = [];
        this.cookieParameters = [];
        this.formParameters = [];
        this.headerParameters = [];
        this.mediaHeaderParameters = [];
        this.authParameters = [];
        this.bodyText = "";

        return;
      }

      this.authParameters = cloneParameters(
        this.selectedRequest.authParameters ?? [],
      );
      this.mediaHeaderParameters = cloneParameters(
        this.selectedRequest.mediaHeaderParameters ?? [],
      );
      this.headerParameters = cloneParameters(
        this.selectedRequest.headerParameters ?? [],
      );
      this.pathParameters = cloneParameters(
        this.selectedRequest.pathParameters ?? [],
      );
      this.queryParameters = cloneParameters(
        this.selectedRequest.queryParameters ?? [],
      );
      this.cookieParameters = cloneParameters(
        this.selectedRequest.cookieParameters ?? [],
      );
      this.formParameters = cloneParameters(
        this.selectedRequest.formParameters ?? [],
      );
      this.bodyText = this.selectedRequest.bodyText ?? "";

      if (this.hasJsonBody) {
        this.formatJsonBody(false);
      }
    },

    applyRuntimeEditsToHar(includePlaceholders = true) {
      const har = structuredCloneSafe(this.selectedRequest?.har ?? {});
      const queryString = this.queryParameters
        .filter(
          (parameter) =>
            this.canUseDeveloperOptions || !parameter.developerOnly,
        )
        .filter(
          (parameter) => parameter.name && String(parameter.value).length > 0,
        )
        .map((parameter) => ({
          name: parameter.name,
          value: String(parameter.value),
        }));

      for (const parameter of this.authParameters.filter(
        (item) => item.location === "query",
      )) {
        const value =
          parameter.value || (includePlaceholders ? parameter.placeholder : "");

        if (value) {
          queryString.push({
            name: parameter.name,
            value,
          });
        }
      }

      har.queryString = queryString;
      har.cookies = this.cookieParameters
        .filter(
          (parameter) => parameter.name && String(parameter.value).length > 0,
        )
        .map((parameter) => ({
          name: parameter.name,
          value: String(parameter.value),
        }));

      for (const parameter of this.authParameters.filter(
        (item) => item.location === "cookie",
      )) {
        const value =
          parameter.value || (includePlaceholders ? parameter.placeholder : "");

        if (value) {
          har.cookies.push({
            name: parameter.name,
            value,
          });
        }
      }

      har.url = buildUrlWithQueryString(
        buildUrlWithPathParameters(
          this.selectedRequest?.urlTemplate ?? har.url,
          this.pathParameters,
        ),
        queryString,
      );

      const editableHeaderNames = [
        ...this.mediaHeaderParameters,
        ...this.headerParameters,
        ...this.authParameters.filter(
          (parameter) => parameter.location === "header",
        ),
      ]
        .map((parameter) => parameter.name.toLowerCase())
        .filter((name) => name.length > 0);

      const headers = (har.headers ?? [])
        .filter(
          (header) => !editableHeaderNames.includes(header.name.toLowerCase()),
        )
        .filter((header) => isValidHeaderName(header.name))
        .map((header) => ({
          name: header.name,
          value: header.value ?? "",
        }));

      for (const parameter of [
        ...this.mediaHeaderParameters,
        ...this.headerParameters,
      ]) {
        if (
          parameter.name &&
          String(parameter.value).length > 0 &&
          isValidHeaderName(parameter.name)
        ) {
          headers.push({
            name: parameter.name,
            value: String(parameter.value),
          });
        }
      }

      for (const parameter of this.authParameters.filter(
        (item) => item.location === "header",
      )) {
        const authValue =
          parameter.value || (includePlaceholders ? parameter.placeholder : "");

        if (authValue && isValidHeaderName(parameter.name)) {
          headers.push({
            name: parameter.name,
            value: `${parameter.prefix ?? ""}${authValue}`,
          });
        }
      }

      har.headers = headers;

      if (har.postData) {
        if (this.hasJsonBody) {
          this.formatJsonBody(false);
        }

        if (this.hasFormUrlEncodedBody) {
          const formParameters = this.editableFormParameters();

          this.bodyText = encodeFormParameters(formParameters);
          har.postData = {
            ...har.postData,
            params: formParameters,
            text: this.bodyText,
          };

          return har;
        }

        if (this.hasMultipartFormDataBody) {
          har.postData = {
            ...har.postData,
            params: this.editableMultipartFormParameters(),
            text: "",
          };

          return har;
        }

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

      if (this.hasJsonBody && this.bodyText.trim() !== "") {
        if (!this.formatJsonBody(false)) {
          this.bodyJsonError = this.message(
            "jsonBeforeSending",
            "Body must be valid JSON before sending.",
          );

          return;
        }
      }

      const invalidHeader = [
        ...this.mediaHeaderParameters,
        ...this.headerParameters,
        ...this.authParameters.filter((item) => item.location === "header"),
      ].find((header) => header.name && !isValidHeaderName(header.name));

      if (invalidHeader) {
        this.sendError = this.message(
          "invalidHeaderName",
          "Invalid header name: :name",
          {
            name: invalidHeader.name,
          },
        );

        return;
      }

      const har = this.applyRuntimeEditsToHar(false);
      const isMultipartFormData =
        normalizeContentType(har.postData?.mimeType) === "multipart/form-data";
      const headers = Object.fromEntries(
        (har.headers ?? [])
          .filter(
            (header) =>
              !(
                isMultipartFormData &&
                header.name.toLowerCase() === "content-type"
              ),
          )
          .filter((header) => header.value && !isPlaceholderValue(header.value))
          .map((header) => [header.name, header.value]),
      );
      const method = (har.method ?? "GET").toUpperCase();
      const requestBody = isMultipartFormData
        ? this.multipartFormData()
        : har.postData?.text;

      this.sending = true;

      try {
        const response = await fetch(har.url, {
          method,
          headers,
          credentials: "same-origin",
          body: ["GET", "HEAD"].includes(method) ? undefined : requestBody,
        });
        const contentType = response.headers.get("Content-Type") ?? "";
        const responseBody = await response.text();

        this.response = {
          ok: response.ok,
          status: response.status,
          statusText: response.statusText,
          contentType,
          body: formatResponseBody(responseBody, contentType),
        };
      } catch (error) {
        this.sendError =
          error instanceof Error
            ? error.message
            : this.message(
                "unableToSendRequest",
                "Unable to send this request.",
              );
      } finally {
        this.sending = false;
      }
    },

    formatJsonBody(showErrors = true, value = this.bodyText) {
      this.bodyJsonError = null;
      this.bodyText = value;

      if (!value.trim()) {
        return true;
      }

      try {
        this.bodyText = JSON.stringify(JSON.parse(value), null, 2);

        return true;
      } catch (error) {
        if (showErrors) {
          this.bodyJsonError = this.message(
            "jsonBeforeFormatting",
            "Body must be valid JSON before formatting.",
          );
        }

        return false;
      }
    },

    async copy() {
      const code = this.code;

      try {
        if (!code || !navigator.clipboard?.writeText) {
          throw new Error("Clipboard unavailable");
        }

        await navigator.clipboard.writeText(code);
        this.copied = true;
        window.clearTimeout(this.copyTimeout);
        this.copyTimeout = window.setTimeout(() => {
          this.copied = false;
        }, 1000);

        new FilamentNotification()
          .title(this.message("copiedToClipboard", "Copied to clipboard."))
          .success()
          .send();
      } catch (error) {
        new FilamentNotification()
          .title(this.message("copyFailed", "Copy failed."))
          .danger()
          .send();
      }
    },

    async copyBody() {
      if (this.hasJsonBody) {
        this.formatJsonBody(false);
      }

      if (this.hasFormUrlEncodedBody) {
        this.bodyText = encodeFormParameters(this.editableFormParameters());
      }

      try {
        if (!this.bodyText || !navigator.clipboard?.writeText) {
          throw new Error("Clipboard unavailable");
        }

        await navigator.clipboard.writeText(this.bodyText);

        new FilamentNotification()
          .title(this.message("copiedToClipboard", "Copied to clipboard."))
          .success()
          .send();
      } catch (error) {
        new FilamentNotification()
          .title(this.message("copyFailed", "Copy failed."))
          .danger()
          .send();
      }
    },

    encodedFormBodyText() {
      if (!this.hasFormUrlEncodedBody) {
        return this.bodyText;
      }

      return encodeFormParameters(this.editableFormParameters());
    },

    editableFormParameters() {
      return this.formParameters
        .filter(
          (parameter) =>
            this.canUseDeveloperOptions || !parameter.developerOnly,
        )
        .filter(
          (parameter) => parameter.name && String(parameter.value).length > 0,
        )
        .map((parameter) => ({
          name: parameter.name,
          value: String(parameter.value),
        }));
    },

    editableMultipartFormParameters() {
      return this.formParameters
        .filter(
          (parameter) =>
            this.canUseDeveloperOptions || !parameter.developerOnly,
        )
        .filter((parameter) => parameter.name)
        .flatMap((parameter) => {
          if (parameter.type !== "file") {
            return String(parameter.value).length > 0
              ? [
                  {
                    name: parameter.name,
                    value: String(parameter.value),
                  },
                ]
              : [];
          }

          const files = Array.isArray(parameter.files) ? parameter.files : [];

          if (files.length > 0) {
            return files.map((file) => ({
              name: parameter.name,
              value: "",
              fileName: file.name,
              contentType:
                file.type || parameter.contentType || "application/octet-stream",
            }));
          }

          return [
            {
              name: parameter.name,
              value: "",
              fileName: parameter.value || parameter.name,
              contentType:
                parameter.contentType || "application/octet-stream",
            },
          ];
        });
    },

    multipartFormData() {
      const formData = new FormData();

      for (const parameter of this.formParameters.filter(
        (item) => this.canUseDeveloperOptions || !item.developerOnly,
      )) {
        if (!parameter.name) {
          continue;
        }

        if (parameter.type !== "file") {
          if (String(parameter.value).length > 0) {
            formData.append(parameter.name, String(parameter.value));
          }

          continue;
        }

        for (const file of Array.isArray(parameter.files)
          ? parameter.files
          : []) {
          formData.append(parameter.name, file);
        }
      }

      return formData;
    },

    async copyResponseBody() {
      try {
        if (!this.response?.body || !navigator.clipboard?.writeText) {
          throw new Error("Clipboard unavailable");
        }

        await navigator.clipboard.writeText(this.response.body);

        new FilamentNotification()
          .title(this.message("copiedToClipboard", "Copied to clipboard."))
          .success()
          .send();
      } catch (error) {
        new FilamentNotification()
          .title(this.message("copyFailed", "Copy failed."))
          .danger()
          .send();
      }
    },

    responseStatusClass(status) {
      const value = String(status ?? "");

      return {
        'fi-color-success': value.startsWith("2"),
        'fi-color-warning': value.startsWith("3") || value.startsWith("4"),
        'fi-color-danger': value.startsWith("5"),
        'fi-color-gray': !["2", "3", "4", "5"].some((p) => value.startsWith(p)),
      };
    },

    responseStatusLabel(status) {
      return this.message("responseStatusBadge", "Status: :status", {
        status,
      });
    },

    responseTypeLabel(type) {
      return this.message("responseTypeBadge", "Type: :type", {
        type,
      });
    },

    message(key, fallback, replacements = {}) {
      let message = this.messages[key] ?? fallback;

      for (const [name, value] of Object.entries(replacements)) {
        message = message.replaceAll(`:${name}`, String(value));
      }

      return message;
    },
  };
}

function cloneParameters(parameters) {
  return structuredCloneSafe(parameters);
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
    (currentUrl, parameter) =>
      currentUrl.replace(
        new RegExp(`\\{${escapeRegExp(parameter.name)}\\}`, "g"),
        encodeURIComponent(String(parameter.value ?? "")),
      ),
    url,
  );
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function isPlaceholderValue(value) {
  return /^<[^>]+>$/.test(String(value).trim());
}

function isValidHeaderName(name) {
  return /^[!#$%&'*+\-.^_`|~0-9A-Za-z]+$/.test(String(name));
}

function normalizeContentType(contentType) {
  return String(contentType ?? "")
    .split(";")[0]
    .trim()
    .toLowerCase();
}

function encodeFormParameters(parameters) {
  return parameters
    .map(
      (parameter) =>
        `${encodeFormComponent(parameter.name)}=${encodeFormComponent(parameter.value)}`,
    )
    .join("&");
}

function encodeFormComponent(value) {
  return encodeURIComponent(String(value)).replace(
    /[!'()*]/g,
    (character) => `%${character.charCodeAt(0).toString(16).toUpperCase()}`,
  );
}

function formatResponseBody(body, contentType) {
  if (!contentType.includes("json") || !body) {
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
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function ensureBufferByteLength() {
  if (globalThis.Buffer?.byteLength) {
    return;
  }

  globalThis.Buffer = {
    byteLength(value, encoding = "utf8") {
      const string = String(value);

      if (
        ["ascii", "binary", "latin1"].includes(String(encoding).toLowerCase())
      ) {
        return string.length;
      }

      return new TextEncoder().encode(string).length;
    },
  };
}

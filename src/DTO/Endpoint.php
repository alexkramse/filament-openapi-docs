<?php

namespace Alexkramse\FilamentOpenapiDocs\DTO;

class Endpoint
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<int, array{name: string, in: string, type: string, required: bool, description: ?string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>, default?: mixed}>  $parameters
     * @param  array<int, array{contentType: string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>}>  $requestBodies
     * @param  array<string, array{description: ?string, content: array<string, array{contentType: string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>}>, headers: array<int, array{name: string, type: string, required: bool, description: ?string, schema: array<string, mixed>, example?: mixed, examples: array<mixed>, deprecated: bool}>}>  $responses
     * @param  array<int, array<string, mixed>>  $security
     */
    public function __construct(
        public readonly string $id,
        public readonly string $method,
        public readonly string $path,
        public readonly string $summary,
        public readonly ?string $description,
        public readonly array $tags,
        public readonly array $parameters,
        public readonly array $requestBodies,
        public readonly array $responses,
        public readonly array $security,
        public readonly bool $deprecated,
    ) {}

    public function group(): string
    {
        return $this->tags[0] ?? 'API';
    }

    public function title(): string
    {
        return $this->summary !== '' ? $this->summary : "{$this->method} {$this->path}";
    }

    public function hasRequestBody(): bool
    {
        return $this->requestBodies !== [];
    }
}

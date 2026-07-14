<?php

namespace Alexkramse\FilamentOpenapiDocs\Services;

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\Support\SpecProvider;

class OpenApiDataResolver
{
    /**
     * @var array{
     *     info: array<string, mixed>,
     *     servers: array<int, string>,
     *     endpoints: array<string, array<int, Endpoint>>,
     *     endpointCount: int,
     *     components: array<string, mixed>,
     * }|null
     */
    private ?array $data = null;

    public function __construct(
        private readonly OpenApiParser $parser,
        private readonly SpecProvider $specProvider,
    ) {}

    /**
     * @return array{
     *     info: array<string, mixed>,
     *     servers: array<int, string>,
     *     endpoints: array<string, array<int, Endpoint>>,
     *     endpointCount: int,
     *     components: array<string, mixed>,
     * }
     */
    public function data(): array
    {
        return $this->data ??= $this->parser->parse(
            $this->specProvider->spec(),
        );
    }
}

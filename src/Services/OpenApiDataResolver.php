<?php

namespace Alexkramse\FilamentOpenapiDocs\Services;

use Alexkramse\FilamentOpenapiDocs\DTO\Endpoint;
use Alexkramse\FilamentOpenapiDocs\Support\SpecProvider;
use Alexkramse\FilamentOpenapiDocs\Support\VersionedSpecProvider;

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
    private array $data = [];

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
    public function data(?string $generator = null): array
    {
        $cacheKey = $generator ?? 'default';

        return $this->data[$cacheKey] ??= $this->parser->parse(
            $generator !== null && $this->specProvider instanceof VersionedSpecProvider
                ? $this->specProvider->specFor($generator)
                : $this->specProvider->spec(),
        );
    }
}

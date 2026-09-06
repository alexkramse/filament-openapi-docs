<?php

namespace Alexkramse\FilamentOpenapiDocs\Support;

use Alexkramse\FilamentOpenapiDocs\FilamentOpenApiDocsPlugin;
use Dedoc\Scramble\CacheableGenerator;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Scramble;

class ScrambleSpecProvider implements VersionedSpecProvider
{
    public function __construct(
        private readonly CacheableGenerator $generator,
    ) {}

    public function config(): GeneratorConfig
    {
        return Scramble::getGeneratorConfig(
            FilamentOpenApiDocsPlugin::current()?->getScrambleGenerator()
                ?? config('filament-openapi-docs.scramble.generator', Scramble::DEFAULT_API),
        );
    }

    public function view(): string
    {
        return $this->config()->renderer()->view;
    }

    public function spec(): array
    {
        return $this->specFor(
            FilamentOpenApiDocsPlugin::current()?->getScrambleGenerator()
                ?? config('filament-openapi-docs.scramble.generator', Scramble::DEFAULT_API),
        );
    }

    public function specFor(string $generator): array
    {
        return ($this->generator)(Scramble::getGeneratorConfig($generator));
    }
}

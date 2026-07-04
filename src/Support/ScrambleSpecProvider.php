<?php

namespace Kramarenko\FilamentOpenApiDocs\Support;

use Dedoc\Scramble\CacheableGenerator;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Scramble;

class ScrambleSpecProvider implements SpecProvider
{
    public function __construct(
        private readonly CacheableGenerator $generator,
    ) {}

    public function config(): GeneratorConfig
    {
        return Scramble::getGeneratorConfig(
            config('filament-openapi-docs.scramble.generator', Scramble::DEFAULT_API),
        );
    }

    public function view(): string
    {
        return $this->config()->renderer()->view;
    }

    public function spec(): array
    {
        return ($this->generator)($this->config());
    }
}

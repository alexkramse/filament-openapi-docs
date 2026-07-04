<?php

namespace Kramarenko\FilamentOpenApiDocs\Support;

use Dedoc\Scramble\GeneratorConfig;

interface SpecProvider
{
    public function config(): GeneratorConfig;

    public function view(): string;

    /**
     * @return array<string, mixed>
     */
    public function spec(): array;
}

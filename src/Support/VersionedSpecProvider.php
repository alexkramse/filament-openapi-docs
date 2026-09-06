<?php

namespace Alexkramse\FilamentOpenapiDocs\Support;

interface VersionedSpecProvider extends SpecProvider
{
    /**
     * @return array<string, mixed>
     */
    public function specFor(string $generator): array;
}

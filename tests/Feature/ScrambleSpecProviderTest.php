<?php

use Alexkramse\FilamentOpenapiDocs\Support\ScrambleSpecProvider;
use Dedoc\Scramble\CacheableGenerator;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Scramble;
use Mockery as m;

it('returns scramble renderer view and generated spec', function () {
    $generatorConfig = Scramble::getGeneratorConfig('default');
    $expectedSpec = [
        'openapi' => '3.1.0',
        'info' => [
            'title' => 'Test API',
            'version' => '1.0.0',
        ],
    ];

    $generator = m::mock(CacheableGenerator::class);
    $generator
        ->shouldReceive('__invoke')
        ->once()
        ->with($generatorConfig)
        ->andReturn($expectedSpec);

    $provider = new ScrambleSpecProvider($generator);

    expect($provider->config())->toBeInstanceOf(GeneratorConfig::class)
        ->and($provider->view())->toBe('scramble::docs')
        ->and($provider->spec())->toBe($expectedSpec);
});

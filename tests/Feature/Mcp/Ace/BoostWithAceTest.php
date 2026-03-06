<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Boost;
use Laravel\Boost\Mcp\Tools\BoostManifest;
use Laravel\Boost\Mcp\Tools\Execute;
use Laravel\Boost\Mcp\Tools\ResolveContext;
use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Server\Contracts\Transport;

function bootAndGetTools(): array
{
    $transport = Mockery::mock(Transport::class);
    $boost = new Boost($transport);

    $reflection = new ReflectionClass($boost);
    $bootMethod = $reflection->getMethod('boot');
    $bootMethod->invoke($boost);

    $toolsProp = $reflection->getProperty('tools');

    return $toolsProp->getValue($boost);
}

test('ace disabled uses legacy tools only', function (): void {
    config()->set('boost.ace.enabled', false);

    $tools = bootAndGetTools();

    expect($tools)->not->toContain(BoostManifest::class)
        ->and($tools)->not->toContain(ResolveContext::class)
        ->and($tools)->not->toContain(Execute::class)
        ->and($tools)->toContain(Tinker::class);
});

test('ace enabled includes ace tools', function (): void {
    config()->set('boost.ace.enabled', true);
    config()->set('boost.ace.legacy_tools', true);

    $tools = bootAndGetTools();

    expect($tools)->toContain(BoostManifest::class)
        ->and($tools)->toContain(ResolveContext::class)
        ->and($tools)->toContain(Execute::class);
});

test('ace enabled with legacy tools includes all tools except tinker', function (): void {
    config()->set('boost.ace.enabled', true);
    config()->set('boost.ace.legacy_tools', true);

    $tools = bootAndGetTools();

    // Execute replaces Tinker when ACE is enabled to avoid duplicate tools
    expect($tools)->toContain(BoostManifest::class)
        ->and($tools)->toContain(ResolveContext::class)
        ->and($tools)->toContain(Execute::class)
        ->and($tools)->not->toContain(Tinker::class);
});

test('ace enabled without legacy tools only has ace tools', function (): void {
    config()->set('boost.ace.enabled', true);
    config()->set('boost.ace.legacy_tools', false);

    $tools = bootAndGetTools();

    expect($tools)->toContain(BoostManifest::class)
        ->and($tools)->toContain(ResolveContext::class)
        ->and($tools)->toContain(Execute::class)
        ->and($tools)->not->toContain(Tinker::class);
});

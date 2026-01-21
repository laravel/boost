<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Laravel\Boost\Mcp\ToolRegistry;
use Laravel\Boost\Mcp\Tools\GetConfig;
use Symfony\Component\Console\Command\Command;
use Tests\Fixtures\ThrowingTool;

beforeEach(function (): void {
    ToolRegistry::clearCache();
    config()->set('app.name', 'TestApp');
    config()->set('boost.mcp.tools.exclude', []);
});

afterEach(function (): void {
    config()->set('boost.mcp.tools.exclude', []);
    ToolRegistry::clearCache();
});

it('exits with error when the tool class is not in the registry', function (): void {
    $this->artisan('boost:execute-tool', [
        'tool' => 'App\\Fake\\NonExistentTool',
        'arguments' => base64_encode('{}'),
    ])->assertExitCode(Command::FAILURE)
        ->expectsOutputToContain('Tool not registered or not allowed');
});

it('exits with error when the tool is in the exclude config', function (): void {
    config()->set('boost.mcp.tools.exclude', [GetConfig::class]);
    ToolRegistry::clearCache();

    $this->artisan('boost:execute-tool', [
        'tool' => GetConfig::class,
        'arguments' => base64_encode(json_encode(['key' => 'app.name'])),
    ])->assertExitCode(Command::FAILURE)
        ->expectsOutputToContain('Tool not registered or not allowed');
});

it('throws TypeError when base64 decoding fails', function (): void {
    $this->artisan('boost:execute-tool', [
        'tool' => GetConfig::class,
        'arguments' => '!!!invalid-base64!!!',
    ])->assertFailed();
})->throws(TypeError::class);

it('exits with error when decoded arguments contain invalid JSON', function (): void {
    $this->artisan('boost:execute-tool', [
        'tool' => GetConfig::class,
        'arguments' => base64_encode('{not valid json'),
    ])->assertExitCode(Command::FAILURE)
        ->expectsOutputToContain('Invalid arguments format');
});

it('outputs JSON with isError false on successful tool execution', function (): void {
    ob_start();
    $exitCode = Artisan::call('boost:execute-tool', [
        'tool' => GetConfig::class,
        'arguments' => base64_encode(json_encode(['key' => 'app.name'])),
    ]);
    $rawOutput = ob_get_clean();

    $json = json_decode($rawOutput, true);

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($json)->toHaveKeys(['isError', 'content'])
        ->and($json['isError'])->toBeFalse();
});

it('outputs JSON with isError true when the tool returns an error response', function (): void {
    ob_start();
    Artisan::call('boost:execute-tool', [
        'tool' => GetConfig::class,
        'arguments' => base64_encode(json_encode(['key' => 'nonexistent.key'])),
    ]);
    $rawOutput = ob_get_clean();

    $json = json_decode($rawOutput, true);

    expect($json['isError'])->toBeTrue();
});

it('catches tool exceptions and outputs error JSON with failure exit code', function (): void {
    config()->set('boost.mcp.tools.include', [ThrowingTool::class]);
    ToolRegistry::clearCache();

    $this->artisan('boost:execute-tool', [
        'tool' => ThrowingTool::class,
        'arguments' => base64_encode('{}'),
    ])->assertExitCode(Command::FAILURE)
        ->expectsOutputToContain('Intentional test exception');
});

<?php

use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\GetConfig;
use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Response;

test('can execute tool in subprocess', function (): void {
    // Create a mock that overrides buildCommand to work with testbench
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $executor->shouldReceive('buildCommand')
        ->once()
        ->andReturnUsing(fn ($toolClass, $arguments): array => buildSubprocessCommand($toolClass, $arguments));

    $response = $executor->execute(GetConfig::class, ['key' => 'app.name']);

    expect($response)->toBeInstanceOf(Response::class);

    // If there's an error, show the error message
    if ($response->isError()) {
        $errorText = (string) $response->content();
        expect(false)->toBeTrue("Tool execution failed with error: {$errorText}");
    }

    expect($response->isError())->toBeFalse();

    // The content should contain the app name (which should be "Laravel" in testbench)
    $textContent = (string) $response->content();
    expect($textContent)->toContain('Laravel');
});

test('rejects unregistered tools', function (): void {
    $executor = app(ToolExecutor::class);
    $response = $executor->execute('NonExistentToolClass');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->isError())->toBeTrue();
});

test('subprocess proves fresh process isolation', function (): void {
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $executor->shouldReceive('buildCommand')
        ->andReturnUsing(fn ($toolClass, $arguments): array => buildSubprocessCommand($toolClass, $arguments));

    $response1 = $executor->execute(Tinker::class, ['code' => 'return getmypid();']);
    $response2 = $executor->execute(Tinker::class, ['code' => 'return getmypid();']);

    expect($response1->isError())->toBeFalse()
        ->and($response2->isError())->toBeFalse();

    $pid1 = json_decode((string) $response1->content(), true)['result'];
    $pid2 = json_decode((string) $response2->content(), true)['result'];

    expect($pid1)->toBeInt()->not->toBe(getmypid())
        ->and($pid2)->toBeInt()->not->toBe(getmypid())
        ->and($pid1)->not()->toBe($pid2);
});

test('subprocess sees modified autoloaded code changes', function (): void {
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $executor->shouldReceive('buildCommand')
        ->andReturnUsing(fn ($toolClass, $arguments): array => buildSubprocessCommand($toolClass, $arguments));

    // Path to the GetConfig tool that we'll temporarily modify
    // TODO: Improve for parallelisation
    $toolPath = dirname(__DIR__, 3).'/src/Mcp/Tools/GetConfig.php';
    $originalContent = file_get_contents($toolPath);

    $cleanup = function () use ($toolPath, $originalContent): void {
        file_put_contents($toolPath, $originalContent);
    };

    try {
        $response1 = $executor->execute(GetConfig::class, ['key' => 'app.name']);

        expect($response1->isError())->toBeFalse();
        $responseData1 = json_decode((string) $response1->content(), true);
        expect($responseData1['value'])->toBe('Laravel'); // Normal testbench app name

        // Modify GetConfig.php to return a different hardcoded value
        $modifiedContent = str_replace(
            "'value' => Config::get(\$key),",
            "'value' => 'MODIFIED_BY_TEST',",
            $originalContent
        );
        file_put_contents($toolPath, $modifiedContent);

        $response2 = $executor->execute(GetConfig::class, ['key' => 'app.name']);
        $responseData2 = json_decode((string) $response2->content(), true);

        expect($response2->isError())->toBeFalse()
            ->and($responseData2['value'])->toBe('MODIFIED_BY_TEST'); // Using updated code, not cached
    } finally {
        $cleanup();
    }
});

/**
 * Build a subprocess command that bootstraps testbench and executes an MCP tool via artisan.
 */
function buildSubprocessCommand(string $toolClass, array $arguments): array
{
    $argumentsEncoded = base64_encode(json_encode($arguments));
    $testScript = sprintf(
        'require_once "%s/vendor/autoload.php"; '.
        'use Orchestra\Testbench\Foundation\Application as Testbench; '.
        'use Orchestra\Testbench\Foundation\Config as TestbenchConfig; '.
        'use Illuminate\Support\Facades\Artisan; '.
        'use Symfony\Component\Console\Output\BufferedOutput; '.
        // Bootstrap testbench like all.php does
        '$app = Testbench::createFromConfig(new TestbenchConfig([]), options: ["enables_package_discoveries" => false]); '.
        (\Illuminate\Container\Container::class.'::setInstance($app); ').
        '$kernel = $app->make("Illuminate\Contracts\Console\Kernel"); '.
        '$kernel->bootstrap(); '.
        // Register the ExecuteToolCommand
        '$kernel->registerCommand(new \Laravel\Boost\Console\ExecuteToolCommand()); '.
        '$output = new BufferedOutput(); '.
        '$result = Artisan::call("boost:execute-tool", ['.
        '  "tool" => "%s", '.
        '  "arguments" => "%s" '.
        '], $output); '.
        'echo $output->fetch();',
        dirname(__DIR__, 3), // Go up from tests/Feature/Mcp to project root
        addslashes($toolClass),
        $argumentsEncoded
    );

    return [PHP_BINARY, '-r', $testScript];
}

test('respects custom timeout parameter', function (): void {
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $executor->shouldReceive('buildCommand')
        ->andReturnUsing(fn ($toolClass, $arguments): array => buildSubprocessCommand($toolClass, $arguments));

    // Test with custom timeout - should succeed with fast code
    $response = $executor->execute(Tinker::class, [
        'code' => 'return "timeout test";',
        'timeout' => 30,
    ]);

    expect($response->isError())->toBeFalse();
});

test('clamps timeout values correctly', function (): void {
    $executor = new ToolExecutor;

    // Test timeout clamping using reflection to access protected method
    $reflection = new ReflectionClass($executor);
    $method = $reflection->getMethod('getTimeout');

    // Test default
    expect($method->invoke($executor, []))->toBe(180);

    // Test custom value
    expect($method->invoke($executor, ['timeout' => 60]))->toBe(60);

    // Test minimum clamp
    expect($method->invoke($executor, ['timeout' => 0]))->toBe(1);

    // Test maximum clamp
    expect($method->invoke($executor, ['timeout' => 1000]))->toBe(600);
});

<?php

use Illuminate\Container\Container;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\DatabaseConnections;
use Laravel\Mcp\Response;
use Laravel\Tinker\TinkerServiceProvider;

test('can execute tool in subprocess', function (): void {
    // Create a mock that overrides buildCommand to work with testbench
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $executor->shouldReceive('buildCommand')
        ->once()
        ->andReturnUsing(buildSubprocessCommand(...));

    $response = $executor->execute(DatabaseConnections::class, []);

    expect($response)->toBeInstanceOf(Response::class);

    // If there's an error, show the error message
    if ($response->isError()) {
        $errorText = (string) $response->content();
        expect(false)->toBeTrue("Tool execution failed with error: {$errorText}");
    }

    expect($response->isError())->toBeFalse();

    $textContent = (string) $response->content();
    expect($textContent)->toContain('connections');
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
        ->andReturnUsing(fn (): array => [
            PHP_BINARY, '-r',
            'echo json_encode(["isError" => false, "content" => [["type" => "text", "text" => (string) getmypid()]]]);',
        ]);

    $response1 = $executor->execute(DatabaseConnections::class, []);
    $response2 = $executor->execute(DatabaseConnections::class, []);

    expect($response1->isError())->toBeFalse()
        ->and($response2->isError())->toBeFalse();

    $pid1 = (int) trim((string) $response1->content());
    $pid2 = (int) trim((string) $response2->content());

    expect($pid1)->toBeGreaterThan(0)->not->toBe(getmypid())
        ->and($pid2)->toBeGreaterThan(0)->not->toBe(getmypid())
        ->and($pid1)->not()->toBe($pid2);
});

test('subprocess sees modified autoloaded code changes', function (): void {
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $executor->shouldReceive('buildCommand')
        ->andReturnUsing(buildSubprocessCommand(...));

    // Path to the DatabaseConnections tool that we'll temporarily modify
    // TODO: Improve for parallelisation
    $toolPath = dirname(__DIR__, 3).'/src/Mcp/Tools/DatabaseConnections.php';
    $originalContent = file_get_contents($toolPath);

    $cleanup = function () use ($toolPath, $originalContent): void {
        file_put_contents($toolPath, $originalContent);
    };

    try {
        $response1 = $executor->execute(DatabaseConnections::class, []);

        expect($response1->isError())->toBeFalse();
        $responseData1 = json_decode((string) $response1->content(), true);
        expect($responseData1)->toHaveKey('default_connection');

        // Modify DatabaseConnections.php to return a different hardcoded value
        $modifiedContent = str_replace(
            "'default_connection' => config('database.default'),",
            "'default_connection' => 'MODIFIED_BY_TEST',",
            $originalContent
        );
        file_put_contents($toolPath, $modifiedContent);

        $response2 = $executor->execute(DatabaseConnections::class, []);
        $responseData2 = json_decode((string) $response2->content(), true);

        expect($response2->isError())->toBeFalse()
            ->and($responseData2['default_connection'])->toBe('MODIFIED_BY_TEST');
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
        (Container::class.'::setInstance($app); ').
        '$kernel = $app->make("Illuminate\Contracts\Console\Kernel"); '.
        '$kernel->bootstrap(); '.
        ('app()->register('.TinkerServiceProvider::class.'::class); ').
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
        ->andReturnUsing(buildSubprocessCommand(...));

    // Test with custom timeout - should succeed with a fast tool
    $response = $executor->execute(DatabaseConnections::class, [
        'timeout' => 30,
    ]);

    expect($response->isError())->toBeFalse();
});

test('extractJson strips leading non-JSON content from output', function (): void {
    $executor = new ToolExecutor;
    $reflection = new ReflectionClass($executor);
    $method = $reflection->getMethod('extractJson');

    $clean = '{"isError":false,"content":[]}';
    expect($method->invoke($executor, $clean))->toBe($clean);

    $noisy = "Deprecated: Some\\Class::method(): Implicitly marking parameter \$x as nullable is deprecated in /path/to/file.php on line 28\n{\"isError\":false}";
    expect($method->invoke($executor, $noisy))->toBe('{"isError":false}');

    $multi = "Warning: something\nDeprecated: another\n{\"isError\":false}";
    expect($method->invoke($executor, $multi))->toBe('{"isError":false}');

    $arrayJson = 'Deprecated: something\n[{"id":1}]';
    expect($method->invoke($executor, $arrayJson))->toBe('[{"id":1}]');

    $cleanArray = '[{"id":1}]';
    expect($method->invoke($executor, $cleanArray))->toBe($cleanArray);

    $withArrays = '{"isError":false,"content":[{"type":"text","text":"[1,2,3]"}]}';
    expect($method->invoke($executor, $withArrays))->toBe($withArrays);

    $noisyWithArrays = "Deprecated: something\n" . '{"isError":false,"content":[{"type":"text","text":"value"}]}';
    expect($method->invoke($executor, $noisyWithArrays))->toBe('{"isError":false,"content":[{"type":"text","text":"value"}]}');

    expect($method->invoke($executor, ''))->toBe('');
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

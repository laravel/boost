<?php

use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Boost\Mcp\Tools\GetConfig;
use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Server\Tools\ToolResult;

test('can execute tool inline', function () {
    // Disable process isolation for this test
    config(['boost.process_isolation.enabled' => false]);

    $executor = app(ToolExecutor::class);
    $result = $executor->execute(ApplicationInfo::class, []);

    expect($result)->toBeInstanceOf(ToolResult::class);
});

test('can execute tool with process isolation', function () {
    // Enable process isolation for this test
    config(['boost.process_isolation.enabled' => true]);

    // Create a mock that overrides buildCommand to work with testbench
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $executor->shouldReceive('buildCommand')
        ->once()
        ->andReturnUsing(fn ($toolClass, $arguments) => buildSubprocessCommand($toolClass, $arguments));

    $result = $executor->execute(GetConfig::class, ['key' => 'app.name']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    // If there's an error, extract the text content properly
    if ($result->isError) {
        $errorText = $result->content[0]->text ?? 'Unknown error';
        expect(false)->toBeTrue("Tool execution failed with error: {$errorText}");
    }

    expect($result->isError)->toBeFalse();
    expect($result->content)->toBeArray();

    // The content should contain the app name (which should be "Laravel" in testbench)
    $textContent = $result->content[0]->text ?? '';
    expect($textContent)->toContain('Laravel');
});

test('rejects unregistered tools', function () {
    $executor = app(ToolExecutor::class);
    $result = $executor->execute('NonExistentToolClass');

    expect($result)->toBeInstanceOf(ToolResult::class)
        ->and($result->isError)->toBeTrue();
});

test('subprocess proves fresh process isolation', function () {
    config(['boost.process_isolation.enabled' => true]);

    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $executor->shouldReceive('buildCommand')
        ->andReturnUsing(fn ($toolClass, $arguments) => buildSubprocessCommand($toolClass, $arguments));

    $result1 = $executor->execute(Tinker::class, ['code' => 'return getmypid();']);
    $result2 = $executor->execute(Tinker::class, ['code' => 'return getmypid();']);

    expect($result1->isError)->toBeFalse();
    expect($result2->isError)->toBeFalse();

    $pid1 = json_decode($result1->content[0]->text, true)['result'];
    $pid2 = json_decode($result2->content[0]->text, true)['result'];

    expect($pid1)->toBeInt()->not->toBe(getmypid());
    expect($pid2)->toBeInt()->not->toBe(getmypid());
    expect($pid1)->not()->toBe($pid2);
});

test('subprocess sees modified autoloaded code changes', function () {
    config(['boost.process_isolation.enabled' => true]);

    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $executor->shouldReceive('buildCommand')
        ->andReturnUsing(fn ($toolClass, $arguments) => buildSubprocessCommand($toolClass, $arguments));

    // Path to the GetConfig tool that we'll temporarily modify
    // TODO: Improve for parallelisation
    $toolPath = dirname(__DIR__, 3).'/src/Mcp/Tools/GetConfig.php';
    $originalContent = file_get_contents($toolPath);

    $cleanup = function () use ($toolPath, $originalContent) {
        file_put_contents($toolPath, $originalContent);
    };

    try {
        $result1 = $executor->execute(GetConfig::class, ['key' => 'app.name']);

        expect($result1->isError)->toBeFalse();
        $response1 = json_decode($result1->content[0]->text, true);
        expect($response1['value'])->toBe('Laravel'); // Normal testbench app name

        // Modify GetConfig.php to return a different hardcoded value
        $modifiedContent = str_replace(
            "'value' => Config::get(\$key),",
            "'value' => 'MODIFIED_BY_TEST',",
            $originalContent
        );
        file_put_contents($toolPath, $modifiedContent);

        $result2 = $executor->execute(GetConfig::class, ['key' => 'app.name']);
        $response2 = json_decode($result2->content[0]->text, true);

        expect($result2->isError)->toBeFalse();
        expect($response2['value'])->toBe('MODIFIED_BY_TEST'); // Using updated code, not cached
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
        'Illuminate\Container\Container::setInstance($app); '.
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

<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\ToolRegistry;
use Laravel\Boost\Mcp\Tools\DatabaseConnections;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Mockery\MockInterface;

test('inline executor path resolves a registered tool without spawning a subprocess', function (): void {
    /** @var ToolExecutor&MockInterface $executor */
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();

    // Force the HTTP/non-CLI branch.
    $executor->shouldReceive('shouldRunInSubprocess')->andReturnFalse();

    // Subprocess builder must NOT be invoked under the HTTP path.
    $executor->shouldNotReceive('buildCommand');
    $executor->shouldNotReceive('executeInSubprocess');

    $response = $executor->execute(DatabaseConnections::class, []);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->isError())->toBeFalse();

    $text = (string) $response->content();
    expect($text)->toContain('connections');
});

test('inline executor still rejects unregistered tools', function (): void {
    /** @var ToolExecutor&MockInterface $executor */
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $executor->shouldReceive('shouldRunInSubprocess')->andReturnFalse();

    $response = $executor->execute('NonExistentToolClass');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->isError())->toBeTrue();
});

test('inline executor wraps thrown exceptions as error responses', function (): void {
    $throwingTool = new class extends Tool
    {
        public function handle(Request $request): Response
        {
            throw new RuntimeException('boom inline');
        }
    };

    $toolClass = $throwingTool::class;

    // Bypass registry by binding the class as allowed.
    ToolRegistry::clearCache();
    app()->instance($toolClass, $throwingTool);

    /** @var ToolExecutor&MockInterface $executor */
    $executor = Mockery::mock(ToolExecutor::class)->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $executor->shouldReceive('shouldRunInSubprocess')->andReturnFalse();

    // Bypass the registry guard by calling the inline path directly via reflection.
    $reflection = new ReflectionClass(ToolExecutor::class);
    $method = $reflection->getMethod('executeInline');

    $response = $method->invoke($executor, $toolClass, []);

    expect($response->isError())->toBeTrue()
        ->and((string) $response->content())->toContain('boom inline');
});

test('shouldRunInSubprocess returns true under CLI SAPI', function (): void {
    $executor = new ToolExecutor;

    $reflection = new ReflectionClass($executor);
    $method = $reflection->getMethod('shouldRunInSubprocess');

    // The Pest runner is itself CLI, so the real check should reflect that.
    expect($method->invoke($executor))->toBe(PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
});

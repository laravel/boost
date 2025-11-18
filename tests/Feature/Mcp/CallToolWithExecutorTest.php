<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Methods\CallToolWithExecutor;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\GetConfig;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;

test('handles tool execution with ResponseFactory correctly', function (): void {
    // Mock ToolExecutor to return a Response
    $executor = Mockery::mock(ToolExecutor::class);
    $response = Response::json(['key' => 'app.name', 'value' => 'Laravel']);
    $executor->shouldReceive('execute')
        ->once()
        ->with(GetConfig::class, ['key' => 'app.name'])
        ->andReturn($response);

    // Create a tool instance
    $tool = new GetConfig;

    // Create ServerContext with the tool
    $context = Mockery::mock(ServerContext::class);
    $context->shouldReceive('tools')
        ->once()
        ->andReturn(collect([$tool]));

    // Create JsonRpcRequest mock
    $request = Mockery::mock(JsonRpcRequest::class);
    $request->shouldReceive('get')
        ->with('name')
        ->andReturn($tool->name());
    $request->params = [
        'name' => $tool->name(),
        'arguments' => ['key' => 'app.name'],
    ];
    $request->id = 'test-request-1';

    // Create CallToolWithExecutor instance
    $handler = new CallToolWithExecutor($executor);

    // Execute - this should not throw BadMethodCallException
    // The fix ensures we call ->responses() before ->map() on ResponseFactory
    $jsonRpcResponse = $handler->handle($request, $context);

    // Verify it returns a JsonRpcResponse without throwing BadMethodCallException
    expect($jsonRpcResponse)->toBeInstanceOf(JsonRpcResponse::class);
});

test('handles tool execution error correctly', function (): void {
    // Mock ToolExecutor to throw an exception
    $executor = Mockery::mock(ToolExecutor::class);
    $executor->shouldReceive('execute')
        ->once()
        ->with(GetConfig::class, ['key' => 'app.name'])
        ->andThrow(new RuntimeException('Test error'));

    // Create a tool instance
    $tool = new GetConfig;

    // Create ServerContext with the tool
    $context = Mockery::mock(ServerContext::class);
    $context->shouldReceive('tools')
        ->once()
        ->andReturn(collect([$tool]));

    // Create JsonRpcRequest mock
    $request = Mockery::mock(JsonRpcRequest::class);
    $request->shouldReceive('get')
        ->with('name')
        ->andReturn($tool->name());
    $request->params = [
        'name' => $tool->name(),
        'arguments' => ['key' => 'app.name'],
    ];
    $request->id = 'test-request-2';

    // Create CallToolWithExecutor instance
    $handler = new CallToolWithExecutor($executor);

    // Execute - should handle the exception gracefully without throwing BadMethodCallException
    $jsonRpcResponse = $handler->handle($request, $context);

    // Verify it returns a JsonRpcResponse
    expect($jsonRpcResponse)->toBeInstanceOf(JsonRpcResponse::class);
});

test('handles ResponseFactory with multiple responses correctly', function (): void {
    // Mock ToolExecutor to return a Response
    $executor = Mockery::mock(ToolExecutor::class);
    $response = Response::json(['key' => 'app.name', 'value' => 'Laravel']);
    $executor->shouldReceive('execute')
        ->once()
        ->with(GetConfig::class, ['key' => 'app.name'])
        ->andReturn($response);

    // Create a tool instance
    $tool = new GetConfig;

    // Create ServerContext with the tool
    $context = Mockery::mock(ServerContext::class);
    $context->shouldReceive('tools')
        ->once()
        ->andReturn(collect([$tool]));

    // Create JsonRpcRequest mock
    $request = Mockery::mock(JsonRpcRequest::class);
    $request->shouldReceive('get')
        ->with('name')
        ->andReturn($tool->name());
    $request->params = [
        'name' => $tool->name(),
        'arguments' => ['key' => 'app.name'],
    ];
    $request->id = 'test-request-3';

    // Create CallToolWithExecutor instance
    $handler = new CallToolWithExecutor($executor);

    // Execute - this specifically tests that ->responses()->map() works
    // The fix ensures we call ->responses() before ->map() on ResponseFactory
    // This should not throw BadMethodCallException
    $jsonRpcResponse = $handler->handle($request, $context);

    // Verify it returns a JsonRpcResponse without throwing BadMethodCallException
    expect($jsonRpcResponse)->toBeInstanceOf(JsonRpcResponse::class);
});

test('throws JsonRpcException when tool name is missing', function (): void {
    $executor = Mockery::mock(ToolExecutor::class);
    $context = Mockery::mock(ServerContext::class);

    // Create JsonRpcRequest mock without 'name' parameter
    $request = Mockery::mock(JsonRpcRequest::class);
    $request->shouldReceive('get')
        ->with('name')
        ->andReturn(null);
    $request->id = 'test-request-4';

    $handler = new CallToolWithExecutor($executor);

    try {
        $handler->handle($request, $context);
        expect(false)->toBeTrue('Expected JsonRpcException to be thrown');
    } catch (\Laravel\Mcp\Server\Exceptions\JsonRpcException $e) {
        expect($e->getMessage())->toContain('Missing [name] parameter');
    }
});

test('throws JsonRpcException when tool is not found', function (): void {
    $executor = Mockery::mock(ToolExecutor::class);

    // Create ServerContext with no tools
    $context = Mockery::mock(ServerContext::class);
    $context->shouldReceive('tools')
        ->once()
        ->andReturn(collect([]));

    // Create JsonRpcRequest mock with non-existent tool
    $request = Mockery::mock(JsonRpcRequest::class);
    $request->shouldReceive('get')
        ->with('name')
        ->andReturn('non-existent-tool');
    $request->params = [
        'name' => 'non-existent-tool',
        'arguments' => [],
    ];
    $request->id = 'test-request-5';

    $handler = new CallToolWithExecutor($executor);

    try {
        $handler->handle($request, $context);
        expect(false)->toBeTrue('Expected JsonRpcException to be thrown');
    } catch (\Laravel\Mcp\Server\Exceptions\JsonRpcException $e) {
        expect($e->getMessage())->toContain('Tool [non-existent-tool] not found');
    }
});


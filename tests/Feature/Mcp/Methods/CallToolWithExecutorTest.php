<?php

use Laravel\Boost\Mcp\Methods\CallToolWithExecutor;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\GetConfig;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Exceptions\JsonRpcException;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;

function createServerContext(array $tools = []): ServerContext
{
    return new ServerContext(
        supportedProtocolVersions: ['2025-01-01'],
        serverCapabilities: [],
        serverName: 'test-server',
        serverVersion: '1.0.0',
        instructions: 'Test instructions',
        maxPaginationLength: 100,
        defaultPaginationLength: 50,
        tools: $tools,
        resources: [],
        prompts: [],
    );
}

function createToolRequest(string $toolName, array $arguments = [], int|string $id = 1): JsonRpcRequest
{
    $params = ['name' => $toolName];

    if ($arguments !== []) {
        $params['arguments'] = $arguments;
    }

    return new JsonRpcRequest(
        id: $id,
        method: 'tools/call',
        params: $params
    );
}

test('throws JsonRpcException when name parameter is missing', function (): void {
    $method = new CallToolWithExecutor(app(ToolExecutor::class));
    $context = createServerContext();

    $request = new JsonRpcRequest(id: 1, method: 'tools/call', params: []);

    $method->handle($request, $context);
})->throws(JsonRpcException::class, 'Missing [name] parameter.', -32602);

test('throws JsonRpcException when tool does not exist', function (): void {
    $method = new CallToolWithExecutor(app(ToolExecutor::class));
    $context = createServerContext([GetConfig::class]);

    $method->handle(createToolRequest('non-existent-tool'), $context);
})->throws(JsonRpcException::class, 'Tool [non-existent-tool] not found.', -32602);

test('successful tool execution returns proper response', function (): void {
    $executor = Mockery::mock(ToolExecutor::class);
    $executor->shouldReceive('execute')
        ->once()
        ->with(GetConfig::class, [])
        ->andReturn(Response::text('Success result'));

    $method = new CallToolWithExecutor($executor);
    $context = createServerContext([GetConfig::class]);

    $response = $method->handle(createToolRequest('get-config', id: 42), $context);

    expect($response->toArray())
        ->toMatchArray(['jsonrpc' => '2.0', 'id' => 42])
        ->toHaveKey('result.content')
        ->toHaveKey('result.isError', false);
});

test('tool execution exceptions are caught and returned as error responses', function (): void {
    $executor = Mockery::mock(ToolExecutor::class);
    $executor->shouldReceive('execute')
        ->once()
        ->with(GetConfig::class, [])
        ->andThrow(new RuntimeException('Database connection failed'));

    $method = new CallToolWithExecutor($executor);
    $context = createServerContext([GetConfig::class]);

    $response = $method->handle(createToolRequest('get-config'), $context);

    expect($response->toArray())
        ->toHaveKey('result.isError', true)
        ->and($response->toArray()['result']['content'][0]['text'])
        ->toContain('Tool execution error: Database connection failed');
});

test('arguments are properly passed to executor', function (): void {
    $expectedArgs = ['key' => 'app.name'];

    $executor = Mockery::mock(ToolExecutor::class);
    $executor->shouldReceive('execute')
        ->once()
        ->with(GetConfig::class, $expectedArgs)
        ->andReturn(Response::text('{"key":"app.name","value":"Laravel"}'));

    $method = new CallToolWithExecutor($executor);
    $context = createServerContext([GetConfig::class]);

    $response = $method->handle(createToolRequest('get-config', $expectedArgs), $context);

    expect($response->toArray())->toHaveKey('result.isError', false);
});

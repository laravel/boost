<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\GetConfig;
use Laravel\Mcp\Server\Tools\ToolResult;

beforeEach(function () {
    config()->set('test.key', 'test_value');
    config()->set('nested.config.key', 'nested_value');
    config()->set('app.name', 'Test App');
});

test('it returns config value when key exists', function () {
    $tool = new GetConfig;
    $result = $tool->handle(['key' => 'test.key']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = $result->toArray();
    expect($data['content'][0]['text'])->toContain('"key": "test.key"')
        ->and($data['content'][0]['text'])->toContain('"value": "test_value"');
});

test('it returns nested config value', function () {
    $tool = new GetConfig;
    $result = $tool->handle(['key' => 'nested.config.key']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = $result->toArray();
    expect($data['content'][0]['text'])->toContain('"key": "nested.config.key"')
        ->and($data['content'][0]['text'])->toContain('"value": "nested_value"');
});

test('it returns error when config key does not exist', function () {
    $tool = new GetConfig;
    $result = $tool->handle(['key' => 'nonexistent.key']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = $result->toArray();
    expect($data['isError'])->toBe(true)
        ->and($data['content'][0]['text'])->toContain("Config key 'nonexistent.key' not found.");
});

test('it works with built-in Laravel config keys', function () {
    $tool = new GetConfig;
    $result = $tool->handle(['key' => 'app.name']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = $result->toArray();
    expect($data['content'][0]['text'])->toContain('"key": "app.name"')
        ->and($data['content'][0]['text'])->toContain('"value": "Test App"');
});

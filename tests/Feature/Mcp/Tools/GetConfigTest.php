<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\GetConfig;

beforeEach(function () {
    config()->set('test.key', 'test_value');
    config()->set('nested.config.key', 'nested_value');
    config()->set('app.name', 'Test App');
});

test('it returns config value when key exists', function () {
    $tool = new GetConfig;
    $result = $tool->handle(['key' => 'test.key']);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('"key": "test.key"', '"value": "test_value"');
});

test('it returns nested config value', function () {
    $tool = new GetConfig;
    $result = $tool->handle(['key' => 'nested.config.key']);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('"key": "nested.config.key"', '"value": "nested_value"');
});

test('it returns error when config key does not exist', function () {
    $tool = new GetConfig;
    $result = $tool->handle(['key' => 'nonexistent.key']);

    expect($result)->isToolResult()
        ->toolHasError()
        ->toolTextContains("Config key 'nonexistent.key' not found.");
});

test('it works with built-in Laravel config keys', function () {
    $tool = new GetConfig;
    $result = $tool->handle(['key' => 'app.name']);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('"key": "app.name"', '"value": "Test App"');
});

<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\GetConfig;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    config()->set('test.key', 'test_value');
    config()->set('nested.config.key', 'nested_value');
    config()->set('app.name', 'Test App');
});

test('it returns config value when key exists', function (): void {
    $tool = new GetConfig;
    $response = $tool->handle(new Request(['key' => 'test.key']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('"key": "test.key"', '"value": "test_value"');
});

test('it returns nested config value', function (): void {
    $tool = new GetConfig;
    $response = $tool->handle(new Request(['key' => 'nested.config.key']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('"key": "nested.config.key"', '"value": "nested_value"');
});

test('it returns error when config key does not exist', function (): void {
    $tool = new GetConfig;
    $response = $tool->handle(new Request(['key' => 'nonexistent.key']));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains("Config key 'nonexistent.key' not found.");
});

test('it works with built-in Laravel config keys', function (): void {
    $tool = new GetConfig;
    $response = $tool->handle(new Request(['key' => 'app.name']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('"key": "app.name"', '"value": "Test App"');
});

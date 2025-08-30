<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Boost\Mcp\Tools\GetAbsoluteUrl;
use Laravel\Mcp\Server\Tools\ToolResult;

beforeEach(function () {
    config()->set('app.url', 'http://localhost');
    Route::get('/test', function () {
        return 'test';
    })->name('test.route');
});

test('it returns absolute url for root path by default', function () {
    $tool = new GetAbsoluteUrl;
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false)
        ->and($data['content'][0]['text'])->toBe('http://localhost');
});

test('it returns absolute url for given path', function () {
    $tool = new GetAbsoluteUrl;
    $result = $tool->handle(['path' => '/dashboard']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false)
        ->and($data['content'][0]['text'])->toBe('http://localhost/dashboard');
});

test('it returns absolute url for named route', function () {
    $tool = new GetAbsoluteUrl;
    $result = $tool->handle(['route' => 'test.route']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false)
        ->and($data['content'][0]['text'])->toBe('http://localhost/test');
});

test('it prioritizes path over route when both are provided', function () {
    $tool = new GetAbsoluteUrl;
    $result = $tool->handle(['path' => '/dashboard', 'route' => 'test.route']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false)
        ->and($data['content'][0]['text'])->toBe('http://localhost/dashboard');
});

test('it handles empty path', function () {
    $tool = new GetAbsoluteUrl;
    $result = $tool->handle(['path' => '']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false)
        ->and($data['content'][0]['text'])->toBe('http://localhost');
});

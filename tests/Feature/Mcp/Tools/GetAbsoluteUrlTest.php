<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Boost\Mcp\Tools\GetAbsoluteUrl;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    config()->set('app.url', 'http://localhost');
    Route::get('/test', fn (): string => 'test')->name('test.route');
});

test('it returns absolute url for root path by default', function (): void {
    $tool = new GetAbsoluteUrl;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('http://localhost');
});

test('it returns absolute url for given path', function (): void {
    $tool = new GetAbsoluteUrl;
    $response = $tool->handle(new Request(['path' => '/dashboard']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('http://localhost/dashboard');
});

test('it returns absolute url for named route', function (): void {
    $tool = new GetAbsoluteUrl;
    $response = $tool->handle(new Request(['route' => 'test.route']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('http://localhost/test');
});

test('it prioritizes path over route when both are provided', function (): void {
    $tool = new GetAbsoluteUrl;
    $response = $tool->handle(new Request(['path' => '/dashboard', 'route' => 'test.route']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('http://localhost/dashboard');
});

test('it handles empty path', function (): void {
    $tool = new GetAbsoluteUrl;
    $response = $tool->handle(new Request(['path' => '']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('http://localhost');
});

test('it generates named resource URLs with route parameters', function (): void {
    Route::resource('orders', 'OrderController');

    $response = (new GetAbsoluteUrl)->handle(new Request([
        'route' => 'orders.show',
        'parameters' => ['order' => 42],
    ]));

    expect((string) $response->content())->toBe('http://localhost/orders/42');
});

test('it supports domain parameters, nested resources, and additional query parameters', function (): void {
    Route::domain('{account}.example.test')
        ->get('/projects/{project}/tasks/{task}', fn (): string => 'task')
        ->name('projects.tasks.show');

    $response = (new GetAbsoluteUrl)->handle(new Request([
        'route' => 'projects.tasks.show',
        'parameters' => [
            'account' => 'acme',
            'project' => 12,
            'task' => 34,
            'tab' => 'activity log',
        ],
    ]));

    expect((string) $response->content())->toBe('http://acme.example.test/projects/12/tasks/34?tab=activity%20log');
});

test('it advertises optional route parameters to MCP clients', function (): void {
    $schema = (new GetAbsoluteUrl)->toArray()['inputSchema'];

    expect($schema['properties']['parameters']['type'])->toBe('object')
        ->and($schema['required'] ?? [])->not->toContain('parameters');
});

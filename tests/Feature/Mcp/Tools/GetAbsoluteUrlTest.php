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

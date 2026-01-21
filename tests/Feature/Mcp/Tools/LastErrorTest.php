<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Boost\Mcp\Tools\LastError;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    Cache::forget('boost:last_error');

    $logDir = storage_path('logs');

    if (File::exists($logDir)) {
        File::deleteDirectory($logDir);
    }

    File::ensureDirectoryExists($logDir);
});

it('returns a cached error when available', function (): void {
    Cache::put('boost:last_error', [
        'timestamp' => '2024-01-15 10:00:00',
        'level' => 'error',
        'message' => 'Test cached error message',
        'context' => ['user_id' => 123, 'action' => 'login'],
    ]);

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Test cached error message', '2024-01-15 10:00:00', 'error', 'user_id', '123');
});

it('falls back to a log file when no cached error', function (): void {
    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
    ]);

    Log::debug('Debug message');
    Log::error('File-based error message');
    Log::info('Info message');

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('ERROR', 'File-based error message');
});

it('it returns an error when a log file does not exist and no cache', function (): void {
    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => storage_path('logs/nonexistent.log'),
    ]);

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Log file not found');
});

it('returns an error when no error entry is found in a log file', function (): void {
    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
    ]);

    Log::debug('Debug message');
    Log::info('Info message');
    Log::warning('Warning message');

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Unable to find an ERROR entry');
});

it('uses a daily log driver correctly', function (): void {
    Config::set('logging.default', 'daily');
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
    ]);

    Log::error('Daily driver error');

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Daily driver error');
});

it('does not return info or warning entries', function (): void {
    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
    ]);

    Log::info('This is an info message');
    Log::warning('This is a warning message');
    Log::error('This is the actual error');

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('ERROR', 'This is the actual error')
        ->toolTextDoesNotContain('This is an info message', 'This is a warning message');
});

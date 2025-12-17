<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Mcp\Tools\ReadLogEntries;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    $logDir = storage_path('logs');
    $files = glob($logDir.'/*.log');
    if ($files) {
        foreach ($files as $file) {
            File::delete($file);
        }
    }
});

test('it returns log entries when file exists with single driver', function (): void {
    $logFile = storage_path('logs/laravel.log');

    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => $logFile,
    ]);

    File::ensureDirectoryExists(dirname($logFile));

    $logContent = <<<'LOG'
[2024-01-15 10:00:00] local.DEBUG: First log message
[2024-01-15 10:01:00] local.ERROR: Error occurred
[2024-01-15 10:02:00] local.WARNING: Warning message
LOG;

    File::put($logFile, $logContent);

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 2]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.WARNING: Warning message', 'local.ERROR: Error occurred')
        ->toolTextDoesNotContain('local.DEBUG: First log message');
});

test('it detects daily driver directly and reads configured path', function (): void {
    $basePath = storage_path('logs/laravel.log');
    $logFile = storage_path('logs/laravel-'.date('Y-m-d').'.log');

    Config::set('logging.default', 'daily');
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

    File::ensureDirectoryExists(dirname($logFile));

    $logContent = <<<'LOG'
[2024-01-15 10:00:00] local.DEBUG: Daily log message
LOG;

    File::put($logFile, $logContent);

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.DEBUG: Daily log message');
});

test('it detects daily driver within stack channel', function (): void {
    $basePath = storage_path('logs/laravel.log');
    $logFile = storage_path('logs/laravel-'.date('Y-m-d').'.log');

    Config::set('logging.default', 'stack');
    Config::set('logging.channels.stack', [
        'driver' => 'stack',
        'channels' => ['daily'],
    ]);
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

    File::ensureDirectoryExists(dirname($logFile));

    $logContent = <<<'LOG'
[2024-01-15 10:00:00] local.DEBUG: Stack with daily log message
LOG;

    File::put($logFile, $logContent);

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.DEBUG: Stack with daily log message');
});

test('it uses custom path from daily channel config', function (): void {
    $basePath = storage_path('logs/custom-app.log');
    $logFile = storage_path('logs/custom-app-'.date('Y-m-d').'.log');

    Config::set('logging.default', 'daily');
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

    File::ensureDirectoryExists(dirname($logFile));

    $logContent = <<<'LOG'
[2024-01-15 10:00:00] local.DEBUG: Custom path log message
LOG;

    File::put($logFile, $logContent);

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.DEBUG: Custom path log message');
});

test('it falls back to most recent daily log when today has no logs', function (): void {
    $basePath = storage_path('logs/laravel.log');

    Config::set('logging.default', 'daily');
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

    $logDir = storage_path('logs');
    File::ensureDirectoryExists($logDir);

    // Create a log file for yesterday
    $yesterdayLogFile = $logDir.'/laravel-'.date('Y-m-d', strtotime('-1 day')).'.log';

    $logContent = <<<'LOG'
[2024-01-14 10:00:00] local.DEBUG: Yesterday's log message
LOG;

    File::put($yesterdayLogFile, $logContent);

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains("local.DEBUG: Yesterday's log message");
});

test('it uses single channel path from stack when no daily channel', function (): void {
    $logFile = storage_path('logs/app.log');

    Config::set('logging.default', 'stack');
    Config::set('logging.channels.stack', [
        'driver' => 'stack',
        'channels' => ['single'],
    ]);
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => $logFile,
    ]);

    File::ensureDirectoryExists(dirname($logFile));

    $logContent = <<<'LOG'
[2024-01-15 10:00:00] local.DEBUG: Single in stack log message
LOG;

    File::put($logFile, $logContent);

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.DEBUG: Single in stack log message');
});

test('it returns error when entries argument is invalid', function (): void {
    $tool = new ReadLogEntries;

    // Test with zero
    $response = $tool->handle(new Request(['entries' => 0]));
    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('The "entries" argument must be greater than 0.');

    // Test with negative
    $response = $tool->handle(new Request(['entries' => -5]));
    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('The "entries" argument must be greater than 0.');
});

test('it returns error when log file does not exist', function (): void {
    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
    ]);

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 10]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Log file not found');
});

test('it returns error when log file is empty', function (): void {
    $logFile = storage_path('logs/laravel.log');

    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => $logFile,
    ]);

    File::ensureDirectoryExists(dirname($logFile));
    File::put($logFile, '');

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 5]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Unable to retrieve log entries, or no entries yet.');
});

test('it ignores non-daily log files when selecting most recent daily log', function (): void {
    $basePath = storage_path('logs/laravel.log');
    Config::set('logging.default', 'daily');
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

    $logDir = storage_path('logs');
    File::ensureDirectoryExists($logDir);

    File::put($logDir.'/laravel-2024-01-10.log', '[2024-01-10 10:00:00] local.DEBUG: Daily log from 2024-01-10');
    File::put($logDir.'/laravel-2024-01-15.log', '[2024-01-15 10:00:00] local.DEBUG: Daily log from 2024-01-15');
    File::put($logDir.'/laravel-backup.log', '[2024-01-20 10:00:00] local.DEBUG: Backup log');
    File::put($logDir.'/laravel-error.log', '[2024-01-20 10:00:00] local.DEBUG: Error log');
    File::put($logDir.'/laravel-zzz.log', '[2024-01-20 10:00:00] local.DEBUG: Zzz log');

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Daily log from 2024-01-15')
        ->toolTextDoesNotContain('Backup log')
        ->toolTextDoesNotContain('Error log')
        ->toolTextDoesNotContain('Zzz log');
});

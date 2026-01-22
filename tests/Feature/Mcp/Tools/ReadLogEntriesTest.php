<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Mcp\Tools\ReadLogEntries;
use Laravel\Mcp\Request;

function createLogFile(string $path, string $content): void
{
    File::ensureDirectoryExists(dirname($path));
    File::put($path, $content);
}

beforeEach(function (): void {
    $logDir = storage_path('logs');
    File::ensureDirectoryExists($logDir);
    File::cleanDirectory($logDir);
});

it('returns log entries when a file exists with single driver', function (): void {
    $logFile = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log');

    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => $logFile,
    ]);

    createLogFile($logFile, <<<'LOG'
[2024-01-15 10:00:00] local.DEBUG: First log message
[2024-01-15 10:01:00] local.ERROR: Error occurred
[2024-01-15 10:02:00] local.WARNING: Warning message
LOG);

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 2]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.WARNING: Warning message', 'local.ERROR: Error occurred')
        ->toolTextDoesNotContain('local.DEBUG: First log message');
});

it('detects a daily driver directly and reads a configured path', function (): void {
    $basePath = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log');
    $logFile = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel-'.date('Y-m-d').'.log');

    Config::set('logging.default', 'daily');
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

    createLogFile($logFile, '[2024-01-15 10:00:00] local.DEBUG: Daily log message');

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.DEBUG: Daily log message');
});

it('detects a daily driver within stack channel', function (): void {
    $basePath = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log');
    $logFile = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel-'.date('Y-m-d').'.log');

    Config::set('logging.default', 'stack');
    Config::set('logging.channels.stack', [
        'driver' => 'stack',
        'channels' => ['daily'],
    ]);
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

    createLogFile($logFile, '[2024-01-15 10:00:00] local.DEBUG: Stack with daily log message');

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.DEBUG: Stack with daily log message');
});

it('falls back to the most recent daily log when today has no logs', function (): void {
    $basePath = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log');
    $logDir = storage_path('logs');

    Config::set('logging.default', 'daily');
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

    $yesterdayLogFile = $logDir.'/laravel-'.date('Y-m-d', strtotime('-1 day')).'.log';
    createLogFile($yesterdayLogFile, "[2024-01-14 10:00:00] local.DEBUG: Yesterday's log message");

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains("local.DEBUG: Yesterday's log message");
});

it('uses single channel path from stack when no daily channel', function (): void {
    $logFile = storage_path('logs'.DIRECTORY_SEPARATOR.'app.log');

    Config::set('logging.default', 'stack');
    Config::set('logging.channels.stack', [
        'driver' => 'stack',
        'channels' => ['single'],
    ]);
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => $logFile,
    ]);

    createLogFile($logFile, '[2024-01-15 10:00:00] local.DEBUG: Single in stack log message');

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.DEBUG: Single in stack log message');
});

it('ignores non-daily log files when selecting most recent daily log', function (): void {
    $basePath = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log');
    $logDir = storage_path('logs');

    Config::set('logging.default', 'daily');
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

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

it('prioritizes the first channel with a path when the stack has multiple channels', function (): void {
    $dailyLogFile = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel-'.date('Y-m-d').'.log');
    $singleLogFile = storage_path('logs'.DIRECTORY_SEPARATOR.'single.log');

    Config::set('logging.default', 'stack');
    Config::set('logging.channels.stack', [
        'driver' => 'stack',
        'channels' => ['daily', 'single'],
    ]);
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log'),
    ]);
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => $singleLogFile,
    ]);

    createLogFile($dailyLogFile, '[2024-01-15 10:00:00] local.DEBUG: Daily channel log');
    createLogFile($singleLogFile, '[2024-01-15 10:00:00] local.DEBUG: Single channel log');

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Daily channel log')
        ->toolTextDoesNotContain('Single channel log');
});

it('handles missing channel configuration gracefully', function (): void {
    $logFile = storage_path('logs'.DIRECTORY_SEPARATOR.'single.log');

    Config::set('logging.default', 'stack');
    Config::set('logging.channels.stack', [
        'driver' => 'stack',
        'channels' => ['nonexistent', 'single'],
    ]);
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => $logFile,
    ]);

    createLogFile($logFile, '[2024-01-15 10:00:00] local.DEBUG: Fallback log');

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 1]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('local.DEBUG: Fallback log');
});

it('returns error when entries argument is invalid', function (): void {
    $tool = new ReadLogEntries;

    $response = $tool->handle(new Request(['entries' => 0]));
    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('The "entries" argument must be greater than 0.');

    $response = $tool->handle(new Request(['entries' => -5]));
    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('The "entries" argument must be greater than 0.');
});

it('returns error when log file does not exist', function (): void {
    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log'),
    ]);

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 10]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Log file not found');
});

it('returns error when log file is empty', function (): void {
    $logFile = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log');

    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => $logFile,
    ]);

    createLogFile($logFile, '');

    $tool = new ReadLogEntries;
    $response = $tool->handle(new Request(['entries' => 5]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Unable to retrieve log entries, or no entries yet.');
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Boost\Mcp\Tools\BrowserLogs;
use Laravel\Boost\Mcp\Tools\LastError;
use Laravel\Mcp\Request;

function useSingleLogChannel(): string
{
    $logFile = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log');

    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => $logFile,
    ]);

    return $logFile;
}

beforeEach(function (): void {
    Cache::forget('boost:last_error');

    $logDir = storage_path('logs');
    File::ensureDirectoryExists($logDir);
    File::cleanDirectory($logDir);
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
    $logFile = useSingleLogChannel();

    $logContent = <<<'LOG'
[2024-01-15 10:00:00] local.DEBUG: Debug message
[2024-01-15 10:01:00] local.ERROR: File-based error message
[2024-01-15 10:02:00] local.INFO: Info message
LOG;

    File::put($logFile, $logContent);

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('ERROR', 'File-based error message');
});

it('keeps timestamps and stack traces inside the last error', function (string $eol): void {
    $logFile = useSingleLogChannel();

    $errorEntry = '[2024-01-15 10:01:00] local.ERROR: Import failed for record [2024-01-15 09:30:00]: invalid date'
        .$eol.'#0 /app/Import.php(42): importRecord()';

    File::put($logFile, $errorEntry.$eol.'[2024-01-15 10:02:00] local.INFO: Import stopped');

    $response = (new LastError)->handle(new Request([]));

    expect($response)->toolHasNoError()
        ->and((string) $response->content())->toBe($errorEntry);
})->with(['LF' => ["\n"], 'CRLF' => ["\r\n"]]);

it('ignores error level text inside later non-error messages', function (string $entry): void {
    $logFile = useSingleLogChannel();

    $errorEntry = '[2024-01-15 10:00:00] local.ERROR: Actual application failure';

    File::put($logFile, $errorEntry."\n".$entry);

    $response = (new LastError)->handle(new Request([]));

    expect($response)->toolHasNoError()
        ->and((string) $response->content())->toBe($errorEntry);
})->with([
    'info message' => ['[2024-01-15 10:01:00] local.INFO: Replayed production.ERROR: Historical failure'],
    'warning message' => ['[2024-01-15 10:01:00] local.WARNING: Replayed production.ERROR: Historical failure'],
    'context value' => ['[2024-01-15 10:01:00] local.DEBUG: Log imported {"original":"production.ERROR: Historical failure"}'],
]);

it('does not find an error when only message text contains an error level', function (): void {
    $logFile = useSingleLogChannel();

    File::put($logFile, '[2024-01-15 10:00:00] local.INFO: Replayed production.ERROR: Historical failure');

    $response = (new LastError)->handle(new Request([]));

    expect($response)->toolHasError()
        ->toolTextContains('Unable to find an ERROR entry');
});

it('recognizes error entries with custom channel names', function (string $channel): void {
    $logFile = useSingleLogChannel();

    $errorEntry = "[2024-01-15 10:00:00] {$channel}.ERROR: Actual application failure";

    File::put($logFile, $errorEntry);

    $response = (new LastError)->handle(new Request([]));

    expect($response)->toolHasNoError()
        ->and((string) $response->content())->toBe($errorEntry);
})->with(['dotted' => ['app.worker'], 'colon' => ['app:worker'], 'spaces' => ['App Worker']]);

it('finds entries logged above the error level', function (string $level): void {
    $logFile = useSingleLogChannel();

    $entry = "[2024-01-15 10:01:00] local.{$level}: Payment gateway unreachable";

    File::put($logFile, '[2024-01-15 10:00:00] local.ERROR: Older failure'."\n".$entry."\n".'[2024-01-15 10:02:00] local.INFO: Retrying');

    $response = (new LastError)->handle(new Request([]));

    expect($response)->toolHasNoError()
        ->and((string) $response->content())->toBe($entry);
})->with(['CRITICAL', 'ALERT', 'EMERGENCY']);

it('finds error entries with custom timestamp formats', function (string $timestamp): void {
    $logFile = useSingleLogChannel();

    $entry = "[{$timestamp}] local.ERROR: Payment gateway unreachable";

    File::put($logFile, $entry."\n"."[{$timestamp}] local.INFO: Retrying");

    $response = (new LastError)->handle(new Request([]));

    expect($response)->toolHasNoError()
        ->and((string) $response->content())->toBe($entry);
})->with([
    'microseconds' => ['2024-01-15 10:00:00.123456'],
    'ISO 8601' => ['2024-01-15T10:00:00.123456+00:00'],
    'UTC suffix' => ['2024-01-15T10:00:00Z'],
]);

it('caches entries logged above the error level', function (): void {
    (new ReflectionProperty(LastError::class, 'listenerRegistered'))->setValue(null, false);

    new LastError;

    Log::critical('Payment gateway unreachable');

    expect(Cache::get('boost:last_error'))->toMatchArray([
        'level' => 'critical',
        'message' => 'Payment gateway unreachable',
    ]);
});

it('it returns an error when a log file does not exist and no cache', function (): void {
    Config::set('logging.default', 'single');
    Config::set('logging.channels.single', [
        'driver' => 'single',
        'path' => storage_path('logs'.DIRECTORY_SEPARATOR.'nonexistent.log'),
    ]);

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Log file not found');
});

it('returns an error when no error entry is found in a log file', function (): void {
    $logFile = useSingleLogChannel();

    $logContent = <<<'LOG'
[2024-01-15 10:00:00] local.DEBUG: Debug message
[2024-01-15 10:01:00] local.INFO: Info message
[2024-01-15 10:02:00] local.WARNING: Warning message
LOG;

    File::put($logFile, $logContent);

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Unable to find an ERROR entry');
});

it('uses a daily log driver correctly', function (): void {
    $basePath = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log');
    $logFile = storage_path('logs/laravel-'.date('Y-m-d').'.log');

    Config::set('logging.default', 'daily');
    Config::set('logging.channels.daily', [
        'driver' => 'daily',
        'path' => $basePath,
    ]);

    File::put($logFile, '[2024-01-15 10:00:00] local.ERROR: Daily driver error');

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Daily driver error');
});

it('falls back to log file when cache is unreachable', function (): void {
    Cache::shouldReceive('get')
        ->with('boost:last_error')
        ->andThrow(new RuntimeException('Cache driver unreachable'));

    $logFile = useSingleLogChannel();

    File::put($logFile, '[2024-01-15 10:00:00] local.ERROR: Fallback error from log file');

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Fallback error from log file');
});

it('finds error in JSON-formatted log entries', function (): void {
    $logFile = useSingleLogChannel();

    $logContent = implode("\n", [
        '{"message":"Info message","context":{},"level":200,"level_name":"INFO","channel":"local","datetime":"2024-01-15T10:00:00+00:00"}',
        '{"message":"JSON error message","context":{},"level":400,"level_name":"ERROR","channel":"local","datetime":"2024-01-15T10:01:00+00:00"}',
        '{"message":"Warning message","context":{},"level":300,"level_name":"WARNING","channel":"local","datetime":"2024-01-15T10:02:00+00:00"}',
    ]);

    File::put($logFile, $logContent);

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('JSON error message')
        ->toolTextDoesNotContain('Info message', 'Warning message');
});

it('finds error in Logstash-formatted log entries', function (): void {
    $logFile = useSingleLogChannel();

    $logContent = implode("\n", [
        '{"@timestamp":"2024-01-15T10:00:00.000Z","@version":1,"host":"server","message":"Logstash info","type":"app","channel":"local","level":"INFO","monolog_level":200}',
        '{"@timestamp":"2024-01-15T10:01:00.000Z","@version":1,"host":"server","message":"Logstash error found","type":"app","channel":"local","level":"ERROR","monolog_level":400}',
        '{"@timestamp":"2024-01-15T10:02:00.000Z","@version":1,"host":"server","message":"Logstash warning","type":"app","channel":"local","level":"WARNING","monolog_level":300}',
    ]);

    File::put($logFile, $logContent);

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Logstash error found')
        ->toolTextDoesNotContain('Logstash info', 'Logstash warning');
});

it('finds critical entries in Logstash-formatted logs', function (): void {
    $logFile = useSingleLogChannel();

    File::put($logFile, implode("\n", [
        '{"@timestamp":"2024-01-15T10:00:00.000Z","message":"Logstash critical found","channel":"local","level":"CRITICAL","monolog_level":500}',
        '{"@timestamp":"2024-01-15T10:01:00.000Z","message":"Logstash info","channel":"local","level":"INFO","monolog_level":200}',
    ]));

    $response = (new LastError)->handle(new Request([]));

    expect($response)->toolHasNoError()
        ->toolTextContains('Logstash critical found')
        ->toolTextDoesNotContain('Logstash info');
});

it('returns error when no error entry in JSON-formatted logs', function (): void {
    $logFile = useSingleLogChannel();

    $logContent = implode("\n", [
        '{"message":"Info message","context":{},"level":200,"level_name":"INFO","channel":"local","datetime":"2024-01-15T10:00:00+00:00"}',
        '{"message":"Debug message","context":{},"level":100,"level_name":"DEBUG","channel":"local","datetime":"2024-01-15T10:01:00+00:00"}',
    ]);

    File::put($logFile, $logContent);

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Unable to find an ERROR entry');
});

it('does not return info or warning entries', function (): void {
    $logFile = useSingleLogChannel();

    $logContent = <<<'LOG'
[2024-01-15 10:00:00] local.INFO: This is an info message
[2024-01-15 10:01:00] local.WARNING: This is a warning message
[2024-01-15 10:02:00] local.ERROR: This is the actual error
LOG;

    File::put($logFile, $logContent);

    $tool = new LastError;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('ERROR', 'This is the actual error')
        ->toolTextDoesNotContain('This is an info message', 'This is a warning message');
});

it('points at the registered browser logs tool name', function (): void {
    expect((new LastError)->description())->toContain((new BrowserLogs)->name());
});

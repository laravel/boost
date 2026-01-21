<?php

declare(strict_types=1);

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Boost\Mcp\Tools\BrowserLogs;
use Laravel\Boost\Middleware\InjectBoost;
use Laravel\Boost\Services\BrowserLogger;
use Laravel\Mcp\Request;

function browserLogPath(): string
{
    return storage_path('logs'.DIRECTORY_SEPARATOR.'browser.log');
}

function createBrowserLogFile(string $content): void
{
    File::ensureDirectoryExists(dirname(browserLogPath()));
    File::put(browserLogPath(), $content);
}

function getBrowserLogContent(): string
{
    return File::get(browserLogPath());
}

beforeEach(function (): void {
    Log::forgetChannel('browser');
    File::ensureDirectoryExists(dirname(browserLogPath()));

    if (File::exists(browserLogPath())) {
        File::delete(browserLogPath());
    }
});

test('it returns log entries when file exists', function (): void {
    createBrowserLogFile(<<<'LOG'
[2024-01-15 10:00:00] browser.DEBUG: console log message {"url":"http://example.com","user_agent":"Mozilla/5.0","timestamp":"2024-01-15T10:00:00.000000Z"}
[2024-01-15 10:01:00] browser.ERROR: JavaScript error occurred {"url":"http://example.com/page","user_agent":"Mozilla/5.0","timestamp":"2024-01-15T10:01:00.000000Z"}
[2024-01-15 10:02:00] browser.WARNING: Warning message {"url":"http://example.com/other","user_agent":"Mozilla/5.0","timestamp":"2024-01-15T10:02:00.000000Z"}
LOG);

    $tool = new BrowserLogs;
    $response = $tool->handle(new Request(['entries' => 2]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('browser.WARNING: Warning message', 'browser.ERROR: JavaScript error occurred')
        ->toolTextDoesNotContain('browser.DEBUG: console log message');
});

test('it returns error when entries argument is invalid', function (): void {
    $tool = new BrowserLogs;

    $response = $tool->handle(new Request(['entries' => 0]));
    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('The "entries" argument must be greater than 0.');

    $response = $tool->handle(new Request(['entries' => -5]));
    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('The "entries" argument must be greater than 0.');
});

test('it returns error when a log file does not exist', function (): void {
    $tool = new BrowserLogs;
    $response = $tool->handle(new Request(['entries' => 10]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('No log file found, probably means no logs yet.');
});

test('it returns error when log file is empty', function (): void {
    createBrowserLogFile('');

    $tool = new BrowserLogs;
    $response = $tool->handle(new Request(['entries' => 5]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Unable to retrieve log entries, or no logs');
});

test('browser logger script contains required functionality', function (): void {
    Route::post('/_boost/browser-logs', fn () => null)->name('boost.browser-logs');

    expect(BrowserLogger::getScript())->toContain(
        'browser-logger-active',
        '/_boost/browser-logs',
        'console.log',
        'console.error',
        'window.onerror'
    );
});

test('browser logs endpoint processes logs correctly', function (): void {
    $response = $this->postJson('/_boost/browser-logs', [
        'logs' => [
            [
                'type' => 'log',
                'timestamp' => '2024-01-15T10:00:00.000Z',
                'data' => ['Test message'],
                'url' => 'http://example.com',
                'userAgent' => 'Mozilla/5.0',
            ],
            [
                'type' => 'error',
                'timestamp' => '2024-01-15T10:01:00.000Z',
                'data' => ['Error occurred'],
                'url' => 'http://example.com/error',
                'userAgent' => 'Chrome/96',
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'logged']);

    expect(browserLogPath())->toBeFile()
        ->and(getBrowserLogContent())
        ->toContain('DEBUG: Test message')
        ->toContain('ERROR: Error occurred')
        ->toContain('http://example.com')
        ->toContain('Mozilla/5.0');
});

test('browser logs endpoint handles complex nested data', function (): void {
    $response = $this->postJson('/_boost/browser-logs', [
        'logs' => [
            [
                'type' => 'unhandled_rejection',
                'timestamp' => '2024-01-15T10:00:00.000Z',
                'data' => [
                    [
                        'message' => 'Unhandled Promise Rejection',
                        'reason' => [
                            'name' => 'TypeError',
                            'message' => 'NetworkError when attempting to fetch resource.',
                            'stack' => '',
                        ],
                    ],
                ],
                'url' => 'http://example.com',
                'userAgent' => 'Mozilla/5.0',
            ],
        ],
    ]);

    $response->assertOk();

    expect(browserLogPath())->toBeFile()
        ->and(getBrowserLogContent())
        ->toContain('ERROR:')
        ->toContain('Unhandled Promise Rejection')
        ->toContain('TypeError')
        ->toContain('NetworkError');
});

test('InjectBoost middleware injects script into HTML response', function (): void {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
</head>
<body>
    <h1>Hello World</h1>
</body>
</html>
HTML;

    $request = HttpRequest::create('/');
    $response = new Response($html, 200, ['Content-Type' => 'text/html']);

    $result = (new InjectBoost)->handle($request, fn ($req): Response => $response);

    expect($result->getContent())
        ->toContain('browser-logger-active')
        ->toContain('</head>')
        ->and(substr_count($result->getContent(), 'browser-logger-active'))->toBe(1);
});

test('InjectBoost middleware does not inject into non-HTML responses', function (): void {
    $json = json_encode(['status' => 'ok']);
    $request = HttpRequest::create('/');
    $response = new Response($json);

    $result = (new InjectBoost)->handle($request, fn ($req): Response => $response);

    expect($result->getContent())
        ->toBe($json)
        ->not->toContain('browser-logger-active');
});

test('InjectBoost middleware does not inject script twice', function (): void {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
    <script id="browser-logger-active">// Already injected</script>
</head>
<body>
    <h1>Hello World</h1>
</body>
</html>
HTML;

    $request = HttpRequest::create('/');
    $response = new Response($html);

    $result = (new InjectBoost)->handle($request, fn ($req): Response => $response);

    expect(substr_count($result->getContent(), 'browser-logger-active'))->toBe(1);
});

test('InjectBoost middleware injects before body tag when no head tag', function (): void {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <h1>Hello World</h1>
</body>
</html>
HTML;

    $request = HttpRequest::create('/');
    $response = new Response($html, 200, ['Content-Type' => 'text/html']);

    $result = (new InjectBoost)->handle($request, fn ($req): Response => $response);

    expect($result->getContent())
        ->toContain('browser-logger-active')
        ->toMatch('/<script[^>]*browser-logger-active[^>]*>.*<\/script>\s*<\/body>/s');
});

<?php

declare(strict_types=1);

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Boost\Mcp\Tools\BrowserLogs;
use Laravel\Boost\Middleware\InjectBoost;
use Laravel\Boost\Services\BrowserLogger;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    // Clean up any existing browser.log file before each test
    $logFile = storage_path('logs/browser.log');
    if (File::exists($logFile)) {
        File::delete($logFile);
    }
});

test('it returns log entries when file exists', function (): void {
    // Create a fake browser.log file with some entries
    $logFile = storage_path('logs/browser.log');
    File::ensureDirectoryExists(dirname($logFile));

    $logContent = <<<'LOG'
[2024-01-15 10:00:00] browser.DEBUG: console log message {"url":"http://example.com","user_agent":"Mozilla/5.0","timestamp":"2024-01-15T10:00:00.000000Z"}
[2024-01-15 10:01:00] browser.ERROR: JavaScript error occurred {"url":"http://example.com/page","user_agent":"Mozilla/5.0","timestamp":"2024-01-15T10:01:00.000000Z"}
[2024-01-15 10:02:00] browser.WARNING: Warning message {"url":"http://example.com/other","user_agent":"Mozilla/5.0","timestamp":"2024-01-15T10:02:00.000000Z"}
LOG;

    File::put($logFile, $logContent);

    $tool = new BrowserLogs;
    $response = $tool->handle(new Request(['entries' => 2]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('browser.WARNING: Warning message', 'browser.ERROR: JavaScript error occurred')
        ->toolTextDoesNotContain('browser.DEBUG: console log message');

    // Response objects don't have toArray(), just verify the text content
    expect($response)->isToolResult();
});

test('it returns error when entries argument is invalid', function (): void {
    $tool = new BrowserLogs;

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
    $tool = new BrowserLogs;
    $response = $tool->handle(new Request(['entries' => 10]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('No log file found, probably means no logs yet.');
});

test('it returns error when log file is empty', function (): void {
    // Create an empty browser.log file
    $logFile = storage_path('logs/browser.log');
    File::ensureDirectoryExists(dirname($logFile));
    File::put($logFile, '');

    $tool = new BrowserLogs;
    $response = $tool->handle(new Request(['entries' => 5]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Unable to retrieve log entries, or no logs');
});

test('@boostJs blade directive renders browser logger script', function (): void {
    // Ensure route exists
    Route::post('/_boost/browser-logs', function (): void {})->name('boost.browser-logs');

    $blade = Blade::compileString('@boostJs');

    expect($blade)->toBe('<?php echo '.\Laravel\Boost\Services\BrowserLogger::class.'::getScript(); ?>');

    // Test that the script contains expected content
    $script = BrowserLogger::getScript();
    expect($script)->toContain('browser-logger-active')
        ->and($script)->toContain('/_boost/browser-logs')
        ->and($script)->toContain('console.log')
        ->and($script)->toContain('console.error')
        ->and($script)->toContain('window.onerror');
});

test('browser logs endpoint processes logs correctly', function (): void {
    Log::shouldReceive('channel')
        ->with('browser')
        ->andReturn($logger = Mockery::mock(\Illuminate\Log\Logger::class));

    $logger->shouldReceive('write')
        ->once()
        ->with('debug', 'Test message', [
            'url' => 'http://example.com',
            'user_agent' => 'Mozilla/5.0',
            'timestamp' => '2024-01-15T10:00:00.000Z',
        ]);

    $logger->shouldReceive('write')
        ->once()
        ->with('error', 'Error occurred', [
            'url' => 'http://example.com/error',
            'user_agent' => 'Chrome/96',
            'timestamp' => '2024-01-15T10:01:00.000Z',
        ]);

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
});

test('browser logs endpoint handles complex nested data', function (): void {
    $this->withoutExceptionHandling();

    Log::shouldReceive('channel')
        ->with('browser')
        ->andReturn($logger = Mockery::mock(\Illuminate\Log\Logger::class));

    $logger->shouldReceive('write')
        ->once()
        ->with('error', 'Unhandled Promise Rejection TypeError NetworkError when attempting to fetch resource. null', Mockery::any());

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
});

test('InjectBoost middleware injects script into HTML response', function (): void {
    $middleware = new InjectBoost;

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
    $response = new \Illuminate\Http\Response($html, 200, ['Content-Type' => 'text/html']);

    $result = $middleware->handle($request, fn ($req): \Illuminate\Http\Response => $response);

    $content = $result->getContent();
    expect($content)->toContain('browser-logger-active')
        ->and($content)->toContain('</head>')
        // Should not inject twice
        ->and(substr_count($content, 'browser-logger-active'))->toBe(1);
});

test('InjectBoost middleware does not inject into non-HTML responses', function (): void {
    $middleware = new InjectBoost;

    $json = json_encode(['status' => 'ok']);

    $request = HttpRequest::create('/');
    $response = new \Illuminate\Http\Response($json);

    $result = $middleware->handle($request, fn ($req): \Illuminate\Http\Response => $response);

    $content = $result->getContent();
    expect($content)->toBe($json)
        ->and($content)->not->toContain('browser-logger-active');
});

test('InjectBoost middleware does not inject script twice', function (): void {
    $middleware = new InjectBoost;

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
    $response = new \Illuminate\Http\Response($html);

    $result = $middleware->handle($request, fn ($req): \Illuminate\Http\Response => $response);

    $content = $result->getContent();
    expect(substr_count($content, 'browser-logger-active'))->toBe(1);
});

test('InjectBoost middleware injects before body tag when no head tag', function (): void {
    $middleware = new InjectBoost;

    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <h1>Hello World</h1>
</body>
</html>
HTML;

    $request = HttpRequest::create('/');
    $response = new \Illuminate\Http\Response($html, 200, ['Content-Type' => 'text/html']);

    $result = $middleware->handle($request, fn ($req): \Illuminate\Http\Response => $response);

    $content = $result->getContent();
    expect($content)->toContain('browser-logger-active')
        ->and($content)->toMatch('/<script[^>]*browser-logger-active[^>]*>.*<\/script>\s*<\/body>/s');
});

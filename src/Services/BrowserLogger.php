<?php

declare(strict_types=1);

namespace Laravel\Boost\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\ComponentAttributeBag;

class BrowserLogger
{
    private const AllBrowserLogTypes = [
        'log',
        'debug',
        'info',
        'warning',
        'error',
        'table',
    ];

    private const BrowserLogLevelTypes = [
        'error' => ['error'],
        'warning' => ['warning', 'error'],
        'info' => ['info', 'warning', 'error'],
        'debug' => self::AllBrowserLogTypes,
    ];

    public static function getScript(): string
    {
        $endpoint = Route::has('boost.browser-logs')
            ? route('boost.browser-logs')
            : '/_boost/browser-logs';

        $attributes = new ComponentAttributeBag([
            'id' => 'browser-logger-active',
        ]);

        $captureTypes = json_encode(self::captureTypes(config('boost.browser_log_levels')), JSON_THROW_ON_ERROR);

        if ($nonce = Vite::cspNonce()) {
            $attributes = $attributes->merge(['nonce' => $nonce]);
        }

        return <<<HTML
<script {$attributes->toHtml()}>
(function() {
    const ENDPOINT = '{$endpoint}';
    const logQueue = [];
    let flushTimeout = null;
    const captureTypes = {$captureTypes};

    console.log('🔍 Browser logger active (MCP server detected). Posting to: ' + ENDPOINT);

    // Store original console methods
    const originalConsole = {
        log: console.log,
        debug: console.debug,
        info: console.info,
        error: console.error,
        warn: console.warn,
        table: console.table
    };

    // Helper to safely stringify values
    function safeStringify(obj) {
        const seen = new WeakSet();
        return JSON.stringify(obj, (key, value) => {
            if (typeof value === 'object' && value !== null) {
                if (seen.has(value)) return '[Circular]';
                seen.add(value);
            }
            if (value instanceof Error) {
                return {
                    name: value.name,
                    message: value.message,
                    stack: value.stack
                };
            }
            return value;
        });
    }

    // Normalize log type for consistency (e.g., 'warn' to 'warning')
    function normalizeType(type) {
        return type === 'warn' ? 'warning' : type;
    }

    // Determine if a log type should be captured based on configured levels
    function shouldCapture(type) {
        return captureTypes.includes(normalizeType(type));
    }

    // Batch and send logs
    function flushLogs() {
        if (logQueue.length === 0) return;

        const batch = logQueue.splice(0, logQueue.length);

        fetch(ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ logs: batch })
        }).catch(err => {
            // Silently fail to avoid infinite loops
            originalConsole.error('Failed to send logs:', err);
        });
    }

    // Debounced flush (100ms)
    function scheduleFlush() {
        if (flushTimeout) clearTimeout(flushTimeout);
        flushTimeout = setTimeout(flushLogs, 100);
    }

    // Intercept console methods
    ['log', 'debug', 'info', 'error', 'warn', 'table'].forEach(method => {
        console[method] = function(...args) {
            // Call original method
            originalConsole[method].apply(console, args);

            // Capture log data
            try {
                if (!shouldCapture(method)) {
                    return;
                }

                logQueue.push({
                    type: method,
                    timestamp: new Date().toISOString(),
                    data: args.map(arg => {
                        try {
                            return typeof arg === 'object' ? JSON.parse(safeStringify(arg)) : arg;
                        } catch (e) {
                            return String(arg);
                        }
                    }),
                    url: window.location.href,
                    userAgent: navigator.userAgent
                });

                scheduleFlush();
            } catch (e) {
                // Fail silently
            }
        };
    });

    // Global error handlers for uncaught errors
    const originalOnError = window.onerror;
    window.onerror = function boostErrorHandler(errorMsg, url, lineNumber, colNumber, error) {
        try {
            if (shouldCapture('error')) {
                logQueue.push({
                    type: 'uncaught_error',
                    timestamp: new Date().toISOString(),
                    data: [{
                        message: errorMsg,
                        filename: url,
                        lineno: lineNumber,
                        colno: colNumber,
                        error: error ? {
                            name: error.name,
                            message: error.message,
                            stack: error.stack
                        } : null
                    }],
                    url: window.location.href,
                    userAgent: navigator.userAgent
                });

                scheduleFlush();
            }

        } catch (e) {
            // Fail silently
        }

        // Call original handler if it exists
        if (originalOnError && typeof originalOnError === 'function') {
            return originalOnError(errorMsg, url, lineNumber, colNumber, error);
        }

        // Let the error continue to propagate
        return false;
    }
    window.addEventListener('error', (event) => {
        try {
            if (!shouldCapture('error')) {
                return false;
            }

            logQueue.push({
                type: 'window_error',
                timestamp: new Date().toISOString(),
                data: [{
                    message: event.message,
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                    error: event.error ? {
                        name: event.error.name,
                        message: event.error.message,
                        stack: event.error.stack
                    } : null
                }],
                url: window.location.href,
                userAgent: navigator.userAgent
            });

            scheduleFlush();
        } catch (e) {
            // Fail silently
        }

        // Let the error continue to propagate
        return false;
    });
    window.addEventListener('unhandledrejection', (event) => {
        try {
            if (!shouldCapture('error')) {
                return false;
            }

            logQueue.push({
                type: 'error',
                timestamp: new Date().toISOString(),
                data: [{
                    message: 'Unhandled Promise Rejection',
                    reason: event.reason instanceof Error ? {
                        name: event.reason.name,
                        message: event.reason.message,
                        stack: event.reason.stack
                    } : event.reason
                }],
                url: window.location.href,
                userAgent: navigator.userAgent
            });

            scheduleFlush();
        } catch (e) {
            // Fail silently
        }

        // Let the rejection continue to propagate
        return false;
    });

    // Flush on page unload
    window.addEventListener('beforeunload', () => {
        if (logQueue.length > 0) {
            navigator.sendBeacon(ENDPOINT, JSON.stringify({ logs: logQueue }));
        }
    });
})();
</script>
HTML;
    }

    /**
     * @return array<int, string>
     */
    private static function captureTypes(mixed $levels): array
    {
        if (! is_array($levels) || $levels === []) {
            return self::AllBrowserLogTypes;
        }

        $captureTypes = [];

        foreach ($levels as $level) {
            if (! is_string($level)) {
                continue;
            }

            $level = strtolower(trim($level));
            $level = $level === 'warn' ? 'warning' : $level;

            foreach (self::BrowserLogLevelTypes[$level] ?? [$level] as $type) {
                $captureTypes[] = $type;
            }
        }

        return array_values(array_unique($captureTypes));
    }
}

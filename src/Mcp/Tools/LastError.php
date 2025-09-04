<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Boost\Concerns\ReadsLogs;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[IsReadOnly]
class LastError extends Tool
{
    use ReadsLogs;

    /**
     * Indicates whether the Log listener has been registered for this process.
     */
    private static bool $listenerRegistered = false;

    public function __construct()
    {
        // Register the listener only once per PHP process.
        if (! self::$listenerRegistered) {
            Log::listen(function (MessageLogged $event) {
                if ($event->level === 'error') {
                    Cache::forever('boost:last_error', [
                        'timestamp' => now()->toDateTimeString(),
                        'level' => $event->level,
                        'message' => $event->message,
                        'context' => [], // $event->context,
                    ]);
                }
            });

            self::$listenerRegistered = true;
        }
    }

    public function description(): string
    {
        return 'Get details of the last error/exception created in this application on the backend. Use browser-log tool for browser errors.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema;
    }

    /**
     * @param array<string> $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        // First, attempt to retrieve the cached last error captured during runtime.
        // This works even if the log driver isn't a file driver, so is the preferred approach
        $cached = Cache::get('boost:last_error');
        if ($cached && isset($cached['timestamp'], $cached['level'], $cached['message'])) {
            $entry = "[{$cached['timestamp']}] {$cached['level']}: {$cached['message']}";
            if (!empty($cached['context'])) {
                $entry .= ' ' . json_encode($cached['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            return ToolResult::text($entry);
        }

        // Locate the correct log file using the shared helper.
        $logFile = $this->resolveLogFilePath();

        if (! is_readable($logFile)) {
            return ToolResult::error("Log file not found at {$logFile}");
        }

        $entry = $this->readLastErrorEntry($logFile);

        if ($entry !== null) {
            return ToolResult::text($entry);
        }

        return ToolResult::error('Unable to find an ERROR entry in the inspected portion of the log file.');
    }
}

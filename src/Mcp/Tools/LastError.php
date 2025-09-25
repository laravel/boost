<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Boost\Concerns\ReadsLogs;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

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
            Log::listen(function (MessageLogged $event): void {
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

    /**
     * The tool's description.
     */
    protected string $description = 'Get details of the last error/exception created in this application on the backend. Use browser-log tool for browser errors.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        // First, attempt to retrieve the cached last error captured during runtime.
        // This works even if the log driver isn't a file driver, so is the preferred approach
        $cached = Cache::get('boost:last_error');
        if ($cached) {
            $entry = "[{$cached['timestamp']}] {$cached['level']}: {$cached['message']}";
            if (! empty($cached['context'])) {
                $entry .= ' '.json_encode($cached['context']);
            }

            return Response::text($entry);
        }

        // Locate the correct log file using the shared helper.
        $logFile = $this->resolveLogFilePath();

        if (! file_exists($logFile)) {
            return Response::error("Log file not found at {$logFile}");
        }

        $entry = $this->readLastErrorEntry($logFile);

        if ($entry !== null) {
            return Response::text($entry);
        }

        return Response::error('Unable to find an ERROR entry in the inspected portion of the log file.');
    }
}

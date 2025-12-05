<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Concerns\ReadsLogs;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ReadLogEntries extends Tool
{
    use ReadsLogs;

    /**
     * The tool's description.
     */
    protected string $description = 'Read the last N log entries from the application log, correctly handling multi-line PSR-3 formatted logs. Only works for log files.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entries' => $schema->integer()
                ->description('Number of log entries to return.')
                ->required(),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $maxEntries = (int) $request->get('entries');

        if ($maxEntries <= 0) {
            return Response::error('The "entries" argument must be greater than 0.');
        }

        // Determine log file path via helper.
        $logFile = $this->resolveLogFilePath();

        if (! file_exists($logFile)) {
            return Response::error("Log file not found at {$logFile}");
        }

        $entries = $this->readLastLogEntries($logFile, $maxEntries);

        if ($entries === []) {
            return Response::text('Unable to retrieve log entries, or no entries yet.');
        }

        $logs = implode("\n\n", $entries);

        if (empty(trim($logs))) {
            return Response::text('No log entries yet.');
        }

        return Response::text($logs);
    }

    // The isNewLogEntry and readLinesReverse helper methods are now provided by the ReadsLogs trait.
}

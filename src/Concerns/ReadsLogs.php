<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Illuminate\Support\Facades\Config;

trait ReadsLogs
{
    /**
     * Regular expression fragments and default chunk-window sizes used when
     * scanning log files. Declaring them once keeps every consumer in sync.
     */
    protected function getTimestampRegex(): string
    {
        return '\\[\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\\]';
    }

    protected function getEntrySplitRegex(): string
    {
        return '/(?='.$this->getTimestampRegex().')/';
    }

    protected function getErrorEntryRegex(): string
    {
        return '/^'.$this->getTimestampRegex().'.*\\.ERROR:/';
    }

    protected function getChunkSizeStart(): int
    {
        return 64 * 1024; // 64 kB
    }

    protected function getChunkSizeMax(): int
    {
        return 1024 * 1024; // 1 MB
    }

    /**
     * Resolve the current log file path based on Laravel's logging configuration.
     */
    protected function resolveLogFilePath(): string
    {
        $channel = Config::get('logging.default');
        $channelConfig = Config::get("logging.channels.{$channel}");

        if (($channelConfig['driver'] ?? null) === 'daily') {
            return storage_path('logs/laravel-'.date('Y-m-d').'.log');
        }

        return storage_path('logs/laravel.log');
    }

    /**
     * Determine if the given line (or entry) is an ERROR log entry.
     */
    protected function isErrorEntry(string $line): bool
    {
        return preg_match($this->getErrorEntryRegex(), $line) === 1;
    }

    /**
     * Retrieve the last $count complete PSR-3 log entries from the log file using
     * chunked reading instead of character-by-character reverse scanning.
     *
     * @return string[]
     */
    protected function readLastLogEntries(string $logFile, int $count): array
    {
        $chunkSize = $this->getChunkSizeStart();

        do {
            $entries = $this->scanLogChunkForEntries($logFile, $chunkSize);

            if (count($entries) >= $count || $chunkSize >= $this->getChunkSizeMax()) {
                break;
            }

            $chunkSize *= 2;
        } while (true);

        return array_slice($entries, -$count);
    }

    /**
     * Return the most recent ERROR log entry, or null if none exists within the
     * inspected window.
     */
    protected function readLastErrorEntry(string $logFile): ?string
    {
        $chunkSize = $this->getChunkSizeStart();

        do {
            $entries = $this->scanLogChunkForEntries($logFile, $chunkSize);

            for ($i = count($entries) - 1; $i >= 0; $i--) {
                if ($this->isErrorEntry($entries[$i])) {
                    return trim((string) $entries[$i]);
                }
            }

            if ($chunkSize >= $this->getChunkSizeMax()) {
                return null;
            }

            $chunkSize *= 2;
        } while (true);
    }

    /**
     * Scan the last $chunkSize bytes of the log file and return an array of
     * complete log entries (oldest ➜ newest).
     *
     * @return string[]
     */
    protected function scanLogChunkForEntries(string $logFile, int $chunkSize): array
    {
        $fileSize = filesize($logFile);
        if ($fileSize === false) {
            return [];
        }

        $handle = fopen($logFile, 'r');
        if (! $handle) {
            return [];
        }

        try {
            $offset = max($fileSize - $chunkSize, 0);
            fseek($handle, $offset);

            // If we started mid-line, discard the partial line to align to next newline.
            if ($offset > 0) {
                fgets($handle);
            }

            $content = stream_get_contents($handle);

            // Split by beginning-of-entry look-ahead (PSR-3 timestamp pattern).
            $entries = preg_split($this->getEntrySplitRegex(), $content, -1, PREG_SPLIT_NO_EMPTY);
            if (! $entries) {
                return [];
            }

            return $entries; // already in chronological order relative to chunk
        } finally {
            fclose($handle);
        }
    }
}

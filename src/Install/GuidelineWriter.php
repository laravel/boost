<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Boost\Contracts\Agent;
use RuntimeException;

class GuidelineWriter
{
    public const NEW = 0;

    public const REPLACED = 1;

    public const FAILED = 2;

    public const NOOP = 3;

    public function __construct(protected Agent $agent) {}

    /**
     * @return \Laravel\Boost\Install\GuidelineWriter::NEW|\Laravel\Boost\Install\GuidelineWriter::REPLACED|\Laravel\Boost\Install\GuidelineWriter::FAILED|\Laravel\Boost\Install\GuidelineWriter::NOOP
     */
    public function write(string $guidelines): int
    {
        if (empty($guidelines)) {
            return self::NOOP;
        }

        $filePath = $this->agent->guidelinesPath();

        $directory = dirname($filePath);
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true)) {
            throw new RuntimeException("Failed to create directory: {$directory}");
        }

        $handle = @fopen($filePath, 'c+');
        if (! $handle) {
            throw new RuntimeException("Failed to open file: {$filePath}");
        }

        try {
            $this->acquireLockWithRetry($handle, $filePath);

            $content = stream_get_contents($handle);

            // Check if guidelines already exist
            $pattern = '/<laravel-boost-guidelines>.*?<\/laravel-boost-guidelines>/s';
            $replacement = "<laravel-boost-guidelines>\n".$guidelines."\n</laravel-boost-guidelines>";
            $replaced = false;

            if (preg_match($pattern, $content)) {
                // Replace ALL existing boost guidelines blocks in-place
                // If the user added guidelines after ours then let's
                // make sure we keep the flow.
                $newContent = preg_replace($pattern, $replacement, $content, 1);
                $replaced = true;
            } else {
                // No existing Boost guidelines found, append to end of existing file
                $frontMatter = '';
                if ($this->agent->frontmatter() && ! str_contains($content, "\n---\n")) {
                    $frontMatter = "---\nalwaysApply: true\n---\n";
                }

                $existingContent = rtrim($content);
                $separatingNewlines = empty($existingContent) ? '' : "\n\n===\n\n";
                $newContent = $frontMatter.$existingContent.$separatingNewlines.$replacement;
            }

            // Normalize multiple blank lines to single blank lines
            $newContent = preg_replace("/\n{3,}/", "\n\n", (string) $newContent);

            // Ensure file content ends with a newline
            if (! str_ends_with((string) $newContent, "\n")) {
                $newContent .= "\n";
            }

            if (ftruncate($handle, 0) === false || fseek($handle, 0) === -1) {
                throw new RuntimeException("Failed to reset file pointer: {$filePath}");
            }

            if (fwrite($handle, (string) $newContent) === false) {
                throw new RuntimeException("Failed to write to file: {$filePath}");
            }

            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        return $replaced ? self::REPLACED : self::NEW;
    }

    protected function acquireLockWithRetry(mixed $handle, string $filePath, int $maxRetries = 3): void
    {
        $attempts = 0;
        $delay = 100000; // Start with 100ms in microseconds

        while ($attempts < $maxRetries) {
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                return;
            }

            $attempts++;

            if ($attempts >= $maxRetries) {
                throw new RuntimeException("Failed to acquire lock on file after {$maxRetries} attempts: {$filePath}");
            }

            // Exponential backoff with jitter
            $jitter = random_int(0, (int) ($delay * 0.1));
            usleep($delay + $jitter);
            $delay *= 2;
        }
    }
}

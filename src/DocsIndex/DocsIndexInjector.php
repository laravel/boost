<?php

declare(strict_types=1);

namespace Laravel\Boost\DocsIndex;

use RuntimeException;

class DocsIndexInjector
{
    private const START_MARKER = '<!-- LARAVEL-DOCS-INDEX:START -->';

    private const END_MARKER = '<!-- LARAVEL-DOCS-INDEX:END -->';

    /**
     * Inject index content between markers in the target file.
     * Prepends to beginning of file, before any existing content.
     */
    public function inject(string $filePath, string $indexContent): void
    {
        $fullPath = base_path($filePath);

        $directory = dirname($fullPath);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true)) {
            throw new RuntimeException("Failed to create directory: {$directory}");
        }

        $handle = @fopen($fullPath, 'c+');

        if (! $handle) {
            throw new RuntimeException("Failed to open file: {$fullPath}");
        }

        try {
            $this->acquireLockWithRetry($handle, $fullPath);

            $content = stream_get_contents($handle) ?: '';

            $block = self::START_MARKER."\n".$indexContent."\n".self::END_MARKER;

            if (str_contains($content, self::START_MARKER) && str_contains($content, self::END_MARKER)) {
                $pattern = '/\n*'.preg_quote(self::START_MARKER, '/').'.*?'.preg_quote(self::END_MARKER, '/').'\n*/s';
                $content = preg_replace($pattern, '', $content);
            }

            $newContent = $block."\n\n".ltrim((string) $content);

            if (ftruncate($handle, 0) === false || fseek($handle, 0) === -1) {
                throw new RuntimeException("Failed to reset file pointer: {$fullPath}");
            }

            if (fwrite($handle, $newContent) === false) {
                throw new RuntimeException("Failed to write to file: {$fullPath}");
            }

            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    protected function acquireLockWithRetry(mixed $handle, string $filePath, int $maxRetries = 3): void
    {
        $attempts = 0;
        $delay = 100000;

        while ($attempts < $maxRetries) {
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                return;
            }

            $attempts++;

            if ($attempts >= $maxRetries) {
                throw new RuntimeException("Failed to acquire lock on file after {$maxRetries} attempts: {$filePath}");
            }

            $jitter = random_int(0, (int) ($delay * 0.1));
            usleep($delay + $jitter);
            $delay *= 2;
        }
    }
}

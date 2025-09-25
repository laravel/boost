<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Detection;

use Laravel\Boost\Install\Contracts\DetectionStrategy;
use Laravel\Boost\Install\Enums\Platform;

class DirectoryDetectionStrategy implements DetectionStrategy
{
    public function detect(array $config, ?Platform $platform = null): bool
    {
        if (! isset($config['paths'])) {
            return false;
        }

        $basePath = $config['basePath'] ?? '';

        foreach ($config['paths'] as $path) {
            $expandedPath = $this->expandPath($path, $platform);

            // If basePath is provided, prepend it to relative paths
            if ($basePath && ! $this->isAbsolutePath($expandedPath)) {
                $expandedPath = $basePath.DIRECTORY_SEPARATOR.$expandedPath;
            }

            if (str_contains($expandedPath, '*')) {
                $matches = glob($expandedPath, GLOB_ONLYDIR);
                if (! empty($matches)) {
                    return true;
                }
            } elseif (is_dir($expandedPath)) {
                return true;
            }
        }

        return false;
    }

    protected function expandPath(string $path, ?Platform $platform = null): string
    {
        if ($platform === Platform::Windows) {
            return preg_replace_callback('/%([^%]+)%/', fn (array $matches) => getenv($matches[1]) ?: $matches[0], $path);
        }

        if (str_starts_with($path, '~')) {
            $home = getenv('HOME');
            if ($home) {
                return str_replace('~', $home, $path);
            }
        }

        return $path;
    }

    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') ||
               str_starts_with($path, '\\') ||
               (strlen($path) > 1 && $path[1] === ':'); // Windows C:
    }
}

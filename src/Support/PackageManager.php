<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class PackageManager
{
    /**
     * Detect which frontend package manager is in use.
     *
     * Detection is based on the presence of lockfiles in the project root:
     * - bun.lockb → bun
     * - pnpm-lock.yaml → pnpm
     * - yarn.lock → yarn
     * - package-lock.json or default → npm
     *
     * @param  string|null  $basePath  The base path to check for lockfiles. Defaults to Laravel's base_path().
     */
    public static function detect(?string $basePath = null): string
    {
        $basePath = $basePath ?? base_path();

        // Check for lockfiles in priority order
        if (file_exists($basePath.DIRECTORY_SEPARATOR.'bun.lockb')) {
            return 'bun';
        }

        if (file_exists($basePath.DIRECTORY_SEPARATOR.'pnpm-lock.yaml')) {
            return 'pnpm';
        }

        if (file_exists($basePath.DIRECTORY_SEPARATOR.'yarn.lock')) {
            return 'yarn';
        }

        // Default to npm (either package-lock.json exists or no lockfile)
        return 'npm';
    }
}

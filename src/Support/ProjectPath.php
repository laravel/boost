<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class ProjectPath
{
    /**
     * Resolve the project root directory.
     *
     * In a standard Laravel application, this returns base_path().
     * When running under Orchestra Testbench (package development),
     * base_path() returns the testbench skeleton path. This method
     * delegates to Testbench's package_path() when available, which
     * correctly resolves to the actual package root.
     */
    public static function resolve(string $path = ''): string
    {
        if (self::isRunningTestbench() && function_exists('Orchestra\Testbench\package_path')) {
            return \Orchestra\Testbench\package_path($path);
        }

        return base_path($path);
    }

    /**
     * Determine whether we are running under Orchestra Testbench.
     *
     * Checks for the TESTBENCH_WORKING_PATH environment variable which is
     * only set when actually running via vendor/bin/testbench, not merely
     * when the Testbench package is installed as a dev dependency.
     */
    public static function isRunningTestbench(): bool
    {
        return isset($_ENV['TESTBENCH_WORKING_PATH'])
            || isset($_SERVER['TESTBENCH_WORKING_PATH'])
            || \defined('TESTBENCH_WORKING_PATH');
    }
}

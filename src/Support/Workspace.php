<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

use function Orchestra\Sidekick\is_testbench_cli;

class Workspace
{
    /**
     * Get the console entry point relative to the working path: `artisan` inside a Laravel
     * application, or `vendor/bin/testbench` for packages running through the Testbench CLI.
     */
    public static function artisan(): string
    {
        return is_testbench_cli() ? 'vendor/bin/testbench' : 'artisan';
    }
}

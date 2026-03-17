<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Boost\Support\Composer;

class Nightwatch
{
    const MCP_URL = 'https://nightwatch.laravel.com/mcp';

    public function isInstalled(): bool
    {
        return array_key_exists('laravel/nightwatch', Composer::packages());
    }

    public function version(): ?string
    {
        $packages = Composer::packages();

        return $packages['laravel/nightwatch'] ?? null;
    }

    public function meetsMinimumVersion(string $minVersion = '1.0.0'): bool
    {
        $version = $this->version();

        if ($version === null) {
            return false;
        }

        // Strip 'v' prefix if present
        $version = ltrim($version, 'v');

        return version_compare($version, $minVersion, '>=');
    }

    public function mcpUrl(): string
    {
        return self::MCP_URL;
    }
}

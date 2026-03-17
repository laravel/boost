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

    public function mcpUrl(): string
    {
        return config('boost.nightwatch.mcp_url')
            ?? env('NIGHTWATCH_MCP_URL')
            ?? self::MCP_URL;
    }
}

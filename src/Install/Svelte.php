<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;

class Svelte
{
    const MCP_URL = 'https://mcp.svelte.dev/mcp';

    public function __construct(private Roster $roster)
    {
        //
    }

    public function isInstalled(): bool
    {
        return $this->roster->uses(Packages::INERTIA_SVELTE);
    }

    public function mcpUrl(): string
    {
        return self::MCP_URL;
    }
}

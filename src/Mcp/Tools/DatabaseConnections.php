<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class DatabaseConnections extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'List the configured database connection names for this application.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $connections = array_keys(config('database.connections', []));

        return Response::json([
            'default_connection' => config('database.default'),
            'connections' => $connections,
        ]);
    }
}

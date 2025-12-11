<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp;

use Laravel\Boost\Mcp\Methods\CallToolWithExecutor;
use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Boost\Mcp\Tools\BrowserLogs;
use Laravel\Boost\Mcp\Tools\DatabaseConnections;
use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;
use Laravel\Boost\Mcp\Tools\GetAbsoluteUrl;
use Laravel\Boost\Mcp\Tools\GetConfig;
use Laravel\Boost\Mcp\Tools\LastError;
use Laravel\Boost\Mcp\Tools\ListArtisanCommands;
use Laravel\Boost\Mcp\Tools\ListAvailableConfigKeys;
use Laravel\Boost\Mcp\Tools\ListAvailableEnvVars;
use Laravel\Boost\Mcp\Tools\ListRoutes;
use Laravel\Boost\Mcp\Tools\ReadLogEntries;
use Laravel\Boost\Mcp\Tools\ReportFeedback;
use Laravel\Boost\Mcp\Tools\SearchDocs;
use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Tool;

class Boost extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Laravel Boost';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = 'Laravel ecosystem MCP server offering database schema access, Artisan commands, error logs, Tinker execution, semantic documentation search and more. Boost helps with code generation.';

    /**
     * The default pagination length for resources that support pagination.
     */
    public int $defaultPaginationLength = 50;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Resource>>
     */
    protected array $resources = [];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [];

    protected function boot(): void
    {
        $this->tools = $this->discoverTools();
        $this->resources = $this->discoverResources();
        $this->prompts = $this->discoverPrompts();

        // Override the tools/call method to use our ToolExecutor
        $this->methods['tools/call'] = CallToolWithExecutor::class;
    }

    /**
     * @param  array<int, class-string>  $availablePrimitives
     * @return array<int, class-string>
     */
    private function discoverPrimitives(array $availablePrimitives, string $type): array
    {
        return collect($availablePrimitives)
            ->diff(config("boost.mcp.{$type}.exclude", []))
            ->merge(
                collect(config("boost.mcp.{$type}.include", []))
                    ->filter(fn (string $class): bool => class_exists($class))
            )
            ->values()
            ->all();
    }

    /**
     * @return array<int, class-string<Tool>>
     */
    protected function discoverTools(): array
    {
        return $this->discoverPrimitives([
            ApplicationInfo::class,
            BrowserLogs::class,
            DatabaseConnections::class,
            DatabaseQuery::class,
            DatabaseSchema::class,
            GetAbsoluteUrl::class,
            GetConfig::class,
            LastError::class,
            ListArtisanCommands::class,
            ListAvailableConfigKeys::class,
            ListAvailableEnvVars::class,
            ListRoutes::class,
            ReadLogEntries::class,
            ReportFeedback::class,
            SearchDocs::class,
            Tinker::class,
        ], 'tools');
    }

    /**
     * @return array<int, class-string<Resource>>
     */
    protected function discoverResources(): array
    {
        return $this->discoverPrimitives([
            Resources\ApplicationInfo::class,
        ], 'resources');
    }

    /**
     * @return array<int, class-string<Prompt>>
     */
    protected function discoverPrompts(): array
    {
        return $this->discoverPrimitives([], 'prompts');
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp;

use Laravel\Boost\Mcp\Methods\CallToolWithExecutor;
use Laravel\Boost\Mcp\Prompts\LaravelCodeSimplifier\LaravelCodeSimplifier;
use Laravel\Boost\Mcp\Prompts\UpgradeLivewirev4\UpgradeLivewireV4;
use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Boost\Mcp\Tools\BoostManifest;
use Laravel\Boost\Mcp\Tools\BrowserLogs;
use Laravel\Boost\Mcp\Tools\DatabaseConnections;
use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;
use Laravel\Boost\Mcp\Tools\Execute;
use Laravel\Boost\Mcp\Tools\GetAbsoluteUrl;
use Laravel\Boost\Mcp\Tools\GetConfig;
use Laravel\Boost\Mcp\Tools\LastError;
use Laravel\Boost\Mcp\Tools\ListArtisanCommands;
use Laravel\Boost\Mcp\Tools\ListAvailableConfigKeys;
use Laravel\Boost\Mcp\Tools\ListAvailableEnvVars;
use Laravel\Boost\Mcp\Tools\ListRoutes;
use Laravel\Boost\Mcp\Tools\ReadLogEntries;
use Laravel\Boost\Mcp\Tools\ResolveContext;
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

        if ($this->aceEnabled()) {
            $this->instructions = 'Laravel Boost MCP server with Adaptive Context Engine. Call boost-manifest first to see available context slices and bundles, then use resolve-context to load what you need in a single batched call. Use execute for running PHP code.';
        }

        $this->methods['tools/call'] = CallToolWithExecutor::class;
    }

    /**
     * @return array<int, class-string<Tool>>
     */
    protected function discoverTools(): array
    {
        if ($this->aceEnabled()) {
            return $this->discoverAceTools();
        }

        return $this->filterPrimitives($this->legacyTools(), 'tools');
    }

    /**
     * @return array<int, class-string<Tool>>
     */
    protected function discoverAceTools(): array
    {
        $tools = [
            BoostManifest::class,
            ResolveContext::class,
            Execute::class,
        ];

        if (config('boost.ace.legacy_tools', true)) {
            $legacyTools = array_filter(
                $this->legacyTools(),
                fn (string $tool): bool => $tool !== Tinker::class,
            );

            $tools = array_merge($tools, $legacyTools);
        }

        return $this->filterPrimitives($tools, 'tools');
    }

    /**
     * The original set of individual MCP tools.
     *
     * @return array<int, class-string<Tool>>
     */
    protected function legacyTools(): array
    {
        return [
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
            SearchDocs::class,
            Tinker::class,
        ];
    }

    protected function aceEnabled(): bool
    {
        return (bool) config('boost.ace.enabled', false);
    }

    /**
     * @return array<int, class-string<Resource>>
     */
    protected function discoverResources(): array
    {
        return $this->filterPrimitives([
            Resources\ApplicationInfo::class,
        ], 'resources');
    }

    /**
     * @return array<int, class-string<Prompt>>
     */
    protected function discoverPrompts(): array
    {
        return $this->filterPrimitives([
            LaravelCodeSimplifier::class,
            UpgradeLivewireV4::class,
        ], 'prompts');
    }

    /**
     * @param  array<int, Tool|Resource|Prompt|class-string>  $availablePrimitives
     * @return array<int, Tool|Resource|Prompt|class-string>
     */
    private function filterPrimitives(array $availablePrimitives, string $type): array
    {
        $excludeList = config("boost.mcp.{$type}.exclude", []);
        $includeList = config("boost.mcp.{$type}.include", []);

        $filtered = collect($availablePrimitives)->reject(function (string|object $item) use ($excludeList): bool {
            $className = is_string($item) ? $item : $item::class;

            return in_array($className, $excludeList, true);
        });

        $explicitlyIncluded = collect($includeList)
            ->filter(fn (string $class): bool => class_exists($class));

        return $filtered
            ->merge($explicitlyIncluded)
            ->values()
            ->all();
    }
}

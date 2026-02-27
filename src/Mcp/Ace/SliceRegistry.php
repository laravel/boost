<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Ace;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Boost\Install\GuidelineComposer;
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
use Laravel\Boost\Mcp\Tools\SearchDocs;

class SliceRegistry
{
    /** @var Collection<string, ContextSlice>|null */
    protected ?Collection $slices = null;

    public function __construct(protected GuidelineComposer $composer) {}

    /**
     * @return Collection<string, ContextSlice>
     */
    public function all(): Collection
    {
        return $this->slices ??= $this->buildSlices();
    }

    public function get(string $id): ?ContextSlice
    {
        return $this->all()->get($id);
    }

    public function has(string $id): bool
    {
        return $this->all()->has($id);
    }

    /**
     * @return Collection<string, ContextSlice>
     */
    protected function buildSlices(): Collection
    {
        return collect($this->dynamicSlices())
            ->merge($this->staticSlices())
            ->keyBy('id');
    }

    /**
     * @return ContextSlice[]
     */
    protected function dynamicSlices(): array
    {
        return [
            new ContextSlice(
                id: 'app-info',
                category: 'framework',
                label: 'PHP/Laravel versions, packages, models',
                estimatedTokens: 150,
                isDynamic: true,
                toolClass: ApplicationInfo::class,
            ),
            new ContextSlice(
                id: 'db-schema',
                category: 'database',
                label: 'Table structures, columns, indexes',
                estimatedTokens: 250,
                isDynamic: true,
                toolClass: DatabaseSchema::class,
                params: ['summary' => 'Return only table names and column types (recommended first)', 'filter' => 'Filter tables by name', 'database' => 'Connection name'],
            ),
            new ContextSlice(
                id: 'db-connections',
                category: 'database',
                label: 'Configured database connections',
                estimatedTokens: 30,
                isDynamic: true,
                toolClass: DatabaseConnections::class,
            ),
            new ContextSlice(
                id: 'db-query',
                category: 'database',
                label: 'Execute read-only SQL query',
                estimatedTokens: 0,
                isDynamic: true,
                toolClass: DatabaseQuery::class,
                params: ['query' => 'SQL SELECT query', 'database' => 'Connection name'],
            ),
            new ContextSlice(
                id: 'routes',
                category: 'framework',
                label: 'Application route definitions',
                estimatedTokens: 200,
                isDynamic: true,
                toolClass: ListRoutes::class,
                params: ['method' => 'Filter by HTTP method', 'path' => 'Filter by path pattern'],
            ),
            new ContextSlice(
                id: 'artisan-commands',
                category: 'framework',
                label: 'Available Artisan commands',
                estimatedTokens: 300,
                isDynamic: true,
                toolClass: ListArtisanCommands::class,
            ),
            new ContextSlice(
                id: 'config-keys',
                category: 'framework',
                label: 'Available config keys in dot notation',
                estimatedTokens: 200,
                isDynamic: true,
                toolClass: ListAvailableConfigKeys::class,
            ),
            new ContextSlice(
                id: 'env-vars',
                category: 'framework',
                label: 'Environment variable names',
                estimatedTokens: 100,
                isDynamic: true,
                toolClass: ListAvailableEnvVars::class,
            ),
            new ContextSlice(
                id: 'get-config',
                category: 'config',
                label: 'Retrieve specific config value',
                estimatedTokens: 50,
                isDynamic: true,
                toolClass: GetConfig::class,
                params: ['key' => 'Config key in dot notation'],
            ),
            new ContextSlice(
                id: 'absolute-url',
                category: 'urls',
                label: 'Generate absolute URL for path or route',
                estimatedTokens: 20,
                isDynamic: true,
                toolClass: GetAbsoluteUrl::class,
                params: ['path' => 'Relative URL path', 'route' => 'Named route'],
            ),
            new ContextSlice(
                id: 'last-error',
                category: 'debug',
                label: 'Most recent application error',
                estimatedTokens: 100,
                isDynamic: true,
                toolClass: LastError::class,
            ),
            new ContextSlice(
                id: 'browser-logs',
                category: 'debug',
                label: 'Browser console log entries',
                estimatedTokens: 200,
                isDynamic: true,
                toolClass: BrowserLogs::class,
                params: ['entries' => 'Number of entries to return'],
            ),
            new ContextSlice(
                id: 'log-entries',
                category: 'debug',
                label: 'Application log entries',
                estimatedTokens: 300,
                isDynamic: true,
                toolClass: ReadLogEntries::class,
                params: ['entries' => 'Number of entries to return'],
            ),
            new ContextSlice(
                id: 'search-docs',
                category: 'docs',
                label: 'Search Laravel ecosystem documentation',
                estimatedTokens: 0,
                isDynamic: true,
                toolClass: SearchDocs::class,
                params: ['queries' => 'Search queries (array)', 'packages' => 'Package names to search'],
            ),
        ];
    }

    /**
     * @return ContextSlice[]
     */
    protected function staticSlices(): array
    {
        return $this->composer->guidelines()
            ->map(fn (array $guideline, string $key) => new ContextSlice(
                id: Str::slug(str_replace('/', '-', $key)),
                category: 'guidelines',
                label: $guideline['description'] ?? $key,
                estimatedTokens: (int) ($guideline['tokens'] ?? 200),
                isDynamic: false,
                guidelineKey: $key,
            ))
            ->values()
            ->all();
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp;

use Laravel\Boost\Mcp\Methods\CallToolWithExecutor;
use Laravel\Boost\Mcp\Prompts\LaravelCodeSimplifier\LaravelCodeSimplifier;
use Laravel\Boost\Mcp\Prompts\UpgradeInertiav3\UpgradeInertiaV3;
use Laravel\Boost\Mcp\Prompts\UpgradeLaravelv13\UpgradeLaravelV13;
use Laravel\Boost\Mcp\Prompts\UpgradeLivewirev4\UpgradeLivewireV4;
use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Boost\Mcp\Tools\BrowserLogs;
use Laravel\Boost\Mcp\Tools\DatabaseConnections;
use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;
use Laravel\Boost\Mcp\Tools\GetAbsoluteUrl;
use Laravel\Boost\Mcp\Tools\LastError;
use Laravel\Boost\Mcp\Tools\ReadLogEntries;
use Laravel\Boost\Mcp\Tools\SearchDocs;
use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Schema\Icon;
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
    protected string $instructions = 'Laravel ecosystem MCP server offering database schema access, error logs, semantic documentation search, and more. Boost helps with code generation.';

    /**
     * The icons exposed to MCP clients.
     *
     * @return list<Icon>
     */
    protected function icons(): array
    {
        $svg = <<<'SVG'
<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="64" height="64" rx="12" fill="url(#bg)"/>
  <g filter="url(#glowA)">
    <ellipse cx="5" cy="5" rx="32" ry="31" fill="#EFB0F9"/>
  </g>
  <g filter="url(#glowB)">
    <ellipse cx="50" cy="51" rx="38" ry="37" fill="#B29FF4"/>
  </g>
  <g filter="url(#glowC)">
    <ellipse cx="17" cy="45" rx="32" ry="31" fill="#D8F8EF"/>
  </g>
  <rect x="15" y="15" width="25" height="25" stroke="#161615" stroke-width="6"/>
  <rect x="22" y="22" width="29" height="29" stroke="#161615" stroke-width="2"/>
  <rect x="27" y="27" width="20" height="20" stroke="#161615" stroke-width="2"/>
  <defs>
    <filter id="glowA" x="-57" y="-56" width="124" height="122" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
      <feGaussianBlur stdDeviation="15"/>
    </filter>
    <filter id="glowB" x="-18" y="-16" width="136" height="134" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
      <feGaussianBlur stdDeviation="15"/>
    </filter>
    <filter id="glowC" x="-45" y="-16" width="124" height="122" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
      <feGaussianBlur stdDeviation="15"/>
    </filter>
    <radialGradient id="bg" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(32 32) scale(32)">
      <stop stop-color="#EEE6F4"/>
      <stop offset="1" stop-color="#DBE0F6"/>
    </radialGradient>
  </defs>
</svg>
SVG;

        return [
            Icon::from('data:image/svg+xml;base64,'.base64_encode($svg), 'image/svg+xml', ['64x64']),
        ];
    }

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
     * @return array<int, class-string<Tool>>
     */
    protected function discoverTools(): array
    {
        return $this->filterPrimitives([
            ApplicationInfo::class,
            BrowserLogs::class,
            DatabaseConnections::class,
            DatabaseQuery::class,
            DatabaseSchema::class,
            GetAbsoluteUrl::class,
            LastError::class,
            ReadLogEntries::class,
            SearchDocs::class,
            Tinker::class,
        ], 'tools');
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
            UpgradeInertiaV3::class,
            UpgradeLaravelV13::class,
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

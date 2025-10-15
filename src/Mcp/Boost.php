<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp;

use DirectoryIterator;
use Laravel\Boost\Mcp\Methods\CallToolWithExecutor;
use Laravel\Boost\Mcp\Resources\ApplicationInfo;
use Laravel\Mcp\Server;

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
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        ApplicationInfo::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [];

    protected function boot(): void
    {
        collect($this->discoverTools())->each(fn (string $tool): string => $this->tools[] = $tool);
        collect($this->discoverResources())->each(fn (string $resource): string => $this->resources[] = $resource);
        collect($this->discoverPrompts())->each(fn (string $prompt): string => $this->prompts[] = $prompt);

        // Override the tools/call method to use our ToolExecutor
        $this->methods['tools/call'] = CallToolWithExecutor::class;
    }

    /**
     * @return array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected function discoverTools(): array
    {
        $tools = [];

        $excludedTools = config('boost.mcp.tools.exclude', []);
        $toolDir = new DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Tools');

        foreach ($toolDir as $toolFile) {
            if ($toolFile->isFile() && $toolFile->getExtension() === 'php') {
                $fqdn = 'Laravel\\Boost\\Mcp\\Tools\\'.$toolFile->getBasename('.php');
                if (class_exists($fqdn) && ! in_array($fqdn, $excludedTools, true)) {
                    $tools[] = $fqdn;
                }
            }
        }

        $extraTools = config('boost.mcp.tools.include', []);
        foreach ($extraTools as $toolClass) {
            if (class_exists($toolClass)) {
                $tools[] = $toolClass;
            }
        }

        return $tools;
    }

    /**
     * @return array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected function discoverResources(): array
    {
        $resources = [];

        $excludedResources = config('boost.mcp.resources.exclude', []);
        $resourceDir = new DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Resources');

        foreach ($resourceDir as $resourceFile) {
            if ($resourceFile->isFile() && $resourceFile->getExtension() === 'php') {
                $fqdn = 'Laravel\\Boost\\Mcp\\Resources\\'.$resourceFile->getBasename('.php');
                if (class_exists($fqdn) && ! in_array($fqdn, $excludedResources, true) && $fqdn !== ApplicationInfo::class) {
                    $resources[] = $fqdn;
                }
            }
        }

        $extraResources = config('boost.mcp.resources.include', []);
        foreach ($extraResources as $resourceClass) {
            if (class_exists($resourceClass)) {
                $resources[] = $resourceClass;
            }
        }

        return $resources;
    }

    /**
     * @return array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected function discoverPrompts(): array
    {
        $prompts = [];

        $excludedPrompts = config('boost.mcp.prompts.exclude', []);
        $promptDir = new DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Prompts');

        foreach ($promptDir as $promptFile) {
            if ($promptFile->isFile() && $promptFile->getExtension() === 'php') {
                $fqdn = 'Laravel\\Boost\\Mcp\\Prompts\\'.$promptFile->getBasename('.php');
                if (class_exists($fqdn) && ! in_array($fqdn, $excludedPrompts, true)) {
                    $prompts[] = $fqdn;
                }
            }
        }

        $extraPrompts = config('boost.mcp.prompts.include', []);
        foreach ($extraPrompts as $promptClass) {
            if (class_exists($promptClass)) {
                $prompts[] = $promptClass;
            }
        }

        return $prompts;
    }
}

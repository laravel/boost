<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp;

use DirectoryIterator;

class ToolRegistry
{
    /** @var array<int, class-string>|null */
    private static ?array $cachedTools = null;

    /**
     * Get all available tools based on the discovery logic from Boost server.
     *
     * @return array<int, class-string>
     */
    public static function getAvailableTools(): array
    {
        if (self::$cachedTools !== null) {
            return self::$cachedTools;
        }

        $tools = [];

        // Discover tools from the Tools directory
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

        // Add extra tools from configuration
        $extraTools = config('boost.mcp.tools.include', []);
        foreach ($extraTools as $toolClass) {
            if (class_exists($toolClass) && ! in_array($toolClass, $tools, true)) {
                $tools[] = $toolClass;
            }
        }

        self::$cachedTools = $tools;

        return $tools;
    }

    /**
     * Check if a tool class is allowed to be executed.
     */
    public static function isToolAllowed(string $toolClass): bool
    {
        return in_array($toolClass, self::getAvailableTools(), true);
    }

    /**
     * Clear the cached tools (useful for testing or when configuration changes).
     */
    public static function clearCache(): void
    {
        self::$cachedTools = null;
    }

    /**
     * Get tool names (class basenames) mapped to their full class names.
     *
     * @return array<string, class-string>
     */
    public static function getToolNames(): array
    {
        $tools = self::getAvailableTools();
        $names = [];

        foreach ($tools as $toolClass) {
            $name = class_basename($toolClass);
            $names[$name] = $toolClass;
        }

        return $names;
    }
}

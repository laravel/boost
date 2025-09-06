<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Support\Facades\Config;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[IsReadOnly]
class ListAvailableConfigKeys extends Tool
{
    public function description(): string
    {
        return 'List all available Laravel configuration keys (from config/*.php) in dot notation.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema;
    }

    /**
     * @param array<string> $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        $configArray = Config::all();
        $dotKeys = $this->flattenToDotNotation($configArray);
        sort($dotKeys);

        return ToolResult::json($dotKeys);
    }

    /**
     * Flatten a multi-dimensional config array into dot notation keys.
     *
     * @param array<int|string, string|array<int|string, string>> $array
     * @return array<int|string, int|string>
     */
    protected function flattenToDotNotation(array $array, string $prefix = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $currentKey = $prefix.$key;

            if (is_array($value)) {
                $results = array_merge($results, $this->flattenToDotNotation($value, $currentKey.'.'));
            } else {
                // Skip numeric keys at the top level (they're likely array values, not config keys)
                if ($prefix === '' && is_numeric($key)) {
                    continue;
                }
                $results[] = $currentKey;
            }
        }

        return $results;
    }
}

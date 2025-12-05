<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Config;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetConfig extends Tool
{
    protected string $description = 'Get the value of a specific config variable using dot notation (e.g., "app.name", "database.default")';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema
                ->string()
                ->description('The config key in dot notation (e.g., "app.name", "database.default")')
                ->required(),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $key = $request->get('key');

        if (! Config::has($key)) {
            return Response::error("Config key '{$key}' not found.");
        }

        return Response::json([
            'key' => $key,
            'value' => Config::get($key),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListAvailableEnvVars extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = '🔧 List all available environment variable names from a given .env file (default .env).';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filename' => $schema->string()
                ->description('The name of the .env file to read (e.g. .env, .env.example). Defaults to .env if not provided.'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $filename = $request->get('filename', '.env');

        $filePath = base_path($filename);

        if (! str_contains($filePath, '.env')) {
            return Response::error('This tool can only read .env files');
        }

        if (! file_exists($filePath)) {
            return Response::error("File not found at '{$filePath}'");
        }

        $envLines = file_get_contents($filePath);

        if (! $envLines) {
            return Response::error('Failed to read .env file.');
        }

        $count = preg_match_all('/^(?!\s*#)\s*([^=\s]+)=/m', $envLines, $matches);

        if (! $count) {
            return Response::error('Failed to parse .env file');
        }

        $envVars = array_map(trim(...), $matches[1]);

        sort($envVars);

        return Response::json($envVars);
    }
}

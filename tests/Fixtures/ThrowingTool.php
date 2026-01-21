<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

class ThrowingTool extends Tool
{
    protected string $description = 'A test tool that always throws an exception';

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        throw new RuntimeException('Intentional test exception');
    }
}

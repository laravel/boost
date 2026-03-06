<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class Execute extends Tool
{
    protected string $description = 'Execute PHP code in the Laravel application context (like Tinker). Use this for write operations, debugging, running code snippets. Runs in an isolated subprocess for safety. Prefer existing artisan commands over custom code. Do not create models directly without explicit user approval.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()
                ->description('PHP code to execute (without opening <?php tags)')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $tinker = app(Tinker::class);

        return $tinker->handle($request);
    }
}

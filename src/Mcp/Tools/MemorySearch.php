<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Memory\MemoryRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

#[IsReadOnly]
class MemorySearch extends Tool
{
    public function __construct(protected MemoryRepository $memory) {}

    /**
     * The tool's description.
     */
    protected string $description = "Search this project's committed memory for relevant decisions and gotchas before working in an area. Pass the file path you are about to edit (e.g. app/Http/Controllers/OrderController.php) to get everything recorded for that area, and/or a keyword to find a specific note. Always check memory before changing code so you do not repeat a settled decision or hit a known trap.";

    /**
     * Determine whether the tool should be registered with the MCP server.
     */
    public function shouldRegister(): bool
    {
        return (bool) config('boost.memory.enabled', true);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('The file path you are about to work on. Returns memories whose glob matches this path.'),
            'query' => $schema->string()
                ->description('A keyword or phrase to match against memory titles and bodies.'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $path = trim((string) $request->get('path'));
        $query = trim((string) $request->get('query'));

        if ($path !== '') {
            // Normalize separators then strip the project root so agents passing absolute
            // paths (the natural default) still match relative globs like app/Http/**.
            $path = str_replace('\\', '/', $path);
            $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

            if (str_starts_with($path, $base)) {
                $path = substr($path, strlen($base));
            }

            $path = ltrim($path, '/');
        }

        if ($path === '' && $query === '') {
            return Response::error('Provide a "path", a "query", or both.');
        }

        try {
            $matches = $this->memory->search($path === '' ? null : $path, $query === '' ? null : $query);
        } catch (Throwable $throwable) {
            return Response::error('Failed to search memory: '.$throwable->getMessage());
        }

        if ($matches === []) {
            return Response::text('No matching project memory. Nothing has been recorded for this yet.');
        }

        $blocks = array_map(static function (array $match): string {
            $applies = implode(', ', $match['applies_to']);

            return '## ['.$match['type'].'] '.$match['title']."\n"
                .'File: '.$match['file'].' (applies to: '.$applies.")\n"
                .$match['body'];
        }, $matches);

        return Response::text(implode("\n\n", $blocks));
    }
}

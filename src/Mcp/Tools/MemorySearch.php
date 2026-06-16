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
    public function __construct(protected MemoryRepository $memoryRepository) {}

    /**
     * The tool's description.
     */
    protected string $description = 'Find the memory file(s) that cover the path you are about to edit. Pass the file path you are working on and Boost returns the .ai/memory/ file(s) whose glob covers it. Then read or grep the returned file for specific decisions, gotchas, and rules. Always check memory before changing code so you do not repeat a settled decision or hit a known trap.';

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
                ->description('The file path you are about to work on. Returns the .ai/memory/ file(s) whose glob covers this path.')
                ->required(),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $path = $this->memoryRepository->relativePath(trim((string) $request->get('path')));

        if ($path === '') {
            return Response::error('Provide a file "path" to find relevant memory files.');
        }

        try {
            $files = $this->memoryRepository->read($path);
        } catch (Throwable $throwable) {
            return Response::error('Failed to search memory: '.$throwable->getMessage());
        }

        if ($files === []) {
            return Response::text('No memory recorded for this path yet.');
        }

        $list = collect($files)
            ->map(function (array $file): string {
                $scope = $file['applies_to'] !== [] ? implode(', ', $file['applies_to']) : 'entire project';
                $relPath = $this->memoryRepository->relativePath($file['path']);

                return "{$relPath} (applies to: {$scope})";
            })
            ->join("\n");

        return Response::text("Memory file(s) for this path:\n\n{$list}\n\nRead or grep the file(s) above to find specific entries.");
    }
}

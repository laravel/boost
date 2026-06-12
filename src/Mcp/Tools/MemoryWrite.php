<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Memory\MemoryRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;

class MemoryWrite extends Tool
{
    public function __construct(protected MemoryRepository $memory) {}

    /**
     * The tool's description.
     */
    protected string $description = 'Record a durable project memory so future agents and teammates do not rediscover it. Use this for a decision (why the project does something a certain way), a gotcha (a non-obvious trap), or a rule (a standing constraint that must always be followed). Pass a glob for the files it applies to (e.g. app/Http/Controllers/**) and Boost files it into a shared, committed markdown note grouped by area. Keep it to a few lines; only record what you would want to read in three months. Do not record secrets, transient state, or anything already obvious from the code.';

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
            'glob' => $schema->string()
                ->description('Glob for the files this memory applies to, for example "app/Http/Controllers/**" or "app/Models/*.php". This routes the memory into a shared area file and is how agents find it later.')
                ->required(),
            'type' => $schema->string()
                ->enum(MemoryRepository::TYPES)
                ->description('The kind of memory: "decision" for a deliberate choice, "gotcha" for a non-obvious trap, "rule" for a standing constraint that must always be followed.')
                ->required(),
            'title' => $schema->string()
                ->description('A short, specific heading, for example "Extend BaseController for tenant scoping".')
                ->required(),
            'note' => $schema->string()
                ->description('A few lines stating the fact plainly. No essays.')
                ->required(),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $glob = trim((string) $request->get('glob'));
        $type = strtolower(trim((string) $request->get('type')));
        $title = trim((string) $request->get('title'));
        $note = trim((string) $request->get('note'));

        if ($glob !== '') {
            // Normalize separators and strip the project root so stored globs are always
            // relative (e.g. app/Http/**), matching how MemorySearch normalizes paths.
            $glob = str_replace('\\', '/', $glob);
            $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

            if (str_starts_with($glob, $base)) {
                $glob = substr($glob, strlen($base));
            }

            $glob = ltrim($glob, '/');
        }

        if ($glob === '' || $title === '' || $note === '') {
            return Response::error('A memory needs a non-empty glob, title, and note.');
        }

        if (! in_array($type, MemoryRepository::TYPES, true)) {
            return Response::error('The "type" must be one of: '.implode(', ', MemoryRepository::TYPES).'.');
        }

        try {
            $result = $this->memory->write($glob, $type, $title, $note);
        } catch (Throwable $throwable) {
            return Response::error('Failed to write memory: '.$throwable->getMessage());
        }

        $verb = $result['created'] ? 'Created' : 'Updated';

        $relPath = ltrim(str_replace(
            rtrim(str_replace('\\', '/', base_path()), '/'),
            '',
            str_replace('\\', '/', $result['file'])
        ), '/');

        return Response::text(
            $verb.' memory in '.$relPath.' as ['.$result['type'].'] '.$result['title'].'. '
            .'Commit this file so the whole team and every agent shares it.'
        );
    }
}

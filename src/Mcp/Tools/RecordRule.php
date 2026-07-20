<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Rules\RuleRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;

class RecordRule extends Tool
{
    public function __construct(protected RuleRepository $ruleRepository)
    {
        //
    }

    /**
     * The tool's description.
     */
    protected string $description = 'Record a durable project rule so the next agent or teammate inherits it instead of working it out again. Use it for a settled decision (why the project does something a certain way), a non-obvious trap, or a standing constraint that must always be followed. Pass a glob for the files it applies to (e.g. app/Http/Controllers/**) and Boost files it into a shared, committed markdown note grouped by area. Keep it to a few lines; only record what you would want to read in three months. Do not record secrets, transient state, or anything already obvious from the code.';

    /**
     * Determine whether the tool should be registered with the MCP server.
     */
    public function shouldRegister(): bool
    {
        return (bool) config('boost.rules.enabled', false);
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
                ->description('Glob for the files this rule applies to, for example "app/Http/Controllers/**" or "app/Models/*.php". This routes the rule into a shared area file and is how agents find it later.')
                ->required(),
            'title' => $schema->string()
                ->description('A short, specific heading, for example "Extend BaseController for tenant scoping".')
                ->required(),
            'note' => $schema->string()
                ->description('A few lines stating the rule plainly. No essays.')
                ->required(),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $glob = trim((string) $request->get('glob'));
        $title = trim((string) $request->get('title'));
        $note = trim((string) $request->get('note'));

        if ($glob !== '') {
            $glob = $this->ruleRepository->normalizeGlob($glob);
        }

        $missing = [];

        if ($glob === '') {
            $missing[] = 'glob';
        }

        if ($title === '') {
            $missing[] = 'title';
        }

        if ($note === '') {
            $missing[] = 'note';
        }

        if ($missing !== []) {
            return Response::error('A rule needs a non-empty glob, title, and note. Missing or empty: '.implode(', ', $missing).'.');
        }

        try {
            $location = $this->ruleRepository->write($glob, $title, $note);
        } catch (Throwable $throwable) {
            return Response::error('Failed to write rule: '.$throwable->getMessage());
        }

        $relPath = $this->ruleRepository->relativePath($location);

        return Response::text(
            "Recorded rule in {$relPath}: {$title}."
        );
    }
}

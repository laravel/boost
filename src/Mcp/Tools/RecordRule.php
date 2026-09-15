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
    protected string $description = 'Record a durable project rule in the shared, committed markdown notes in .ai/rules, grouped by area. Only call this when the user explicitly asks for a rule to be recorded, remembered, or documented. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here", and "don\'t do that again" are work to do, not rules to record. A rule constrains future work across many files; it never describes a single fix. Never call this on your own initiative, as a byproduct of a change, or to summarize what you just did. When in doubt, do not call it. Pass a glob for the files it applies to (e.g. app/Http/Controllers/**). Keep the note to a few lines. Do not record secrets, transient state, or anything already obvious from the code.';

    /**
     * Determine whether the tool should be registered with the MCP server.
     */
    public function shouldRegister(): bool
    {
        return (bool) config('boost.rules.enabled', true);
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

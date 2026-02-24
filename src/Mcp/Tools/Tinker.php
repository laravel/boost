<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class Tinker extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Execute PHP code in the Laravel application context, like artisan tinker. Use this for debugging issues, checking if functions exist, and testing code snippets. You should not create models directly without explicit user approval. Prefer Unit/Feature tests using factories for functionality testing. Prefer existing artisan commands over custom tinker code.';

    /**
     * Get the tool's input schema.
     *
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

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $code = str_replace(['<?php', '?>'], '', (string) $request->get('code'));

        $output = new BufferedOutput;

        try {
            $exitCode = Artisan::call('tinker', [
                '--execute' => $code,
                '--no-ansi' => true,
                '--no-interaction' => true,
            ], $output);
        } catch (Throwable $e) {
            return Response::text($e->getMessage());
        }

        if ($exitCode !== CommandAlias::SUCCESS) {
            return Response::text('Failed to execute tinker: '.$output->fetch());
        }

        return Response::text(trim($output->fetch()));
    }
}

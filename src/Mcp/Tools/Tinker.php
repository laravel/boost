<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class Tinker extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Execute PHP code in the Laravel application context, like artisan tinker. Use this for debugging issues, checking if functions exist, and testing code snippets. You should not create models directly without explicit user approval. Prefer Unit/Feature tests using factories for functionality testing. Prefer existing artisan commands over custom tinker code. Returns the output of the code, as well as whatever is "returned" using "return".';

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
            'timeout' => $schema->integer()
                ->description('Maximum execution time in seconds (default: 180)')
                ->required(),
        ];
    }

    /**
     * Handle the tool request using php artisan tinker --execute.
     */
    public function handle(Request $request): Response
    {
        $code = $this->sanitizeCode((string) $request->get('code'));

        if ($code === '') {
            return Response::error('Please provide code to execute');
        }

        $timeout = $this->clampTimeout($request->get('timeout'));

        $process = new Process(
            command: $this->buildCommand($code),
            timeout: $timeout
        );

        try {
            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            if (! $process->isSuccessful() && $errorOutput) {
                return Response::json([
                    'error' => trim($errorOutput),
                    'type' => 'ProcessError',
                ]);
            }

            return Response::json([
                'output' => $output,
            ]);
        } catch (ProcessTimedOutException) {
            $process->stop();

            return Response::json([
                'error' => "Execution timed out after {$timeout} seconds",
                'type' => 'TimeoutError',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    protected function buildCommand(string $code): array
    {
        return [PHP_BINARY, base_path('artisan'), 'tinker', '--execute='.$code];
    }

    protected function sanitizeCode(string $code): string
    {
        return trim(str_replace(['<?php', '?>'], '', $code));
    }

    protected function clampTimeout(mixed $timeout): int
    {
        return max(1, min(600, (int) ($timeout ?? 180)));
    }
}

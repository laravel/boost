<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Concerns\InteractsWithArtisanCommand;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class Tinker extends Tool
{
    use InteractsWithArtisanCommand;

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

        $wrappedCode = $this->wrapCodeToPreserveReturnValue($code);

        try {
            $output = $this->callArtisanCommand('tinker', [
                '--execute' => $wrappedCode,
            ]);

            return Response::json([
                'output' => $output,
            ]);
        } catch (Exception $exception) {
            return Response::json([
                'error' => $exception->getMessage(),
                'type' => 'ExecutionError',
            ]);
        }
    }

    protected function sanitizeCode(string $code): string
    {
        return trim(str_replace(['<?php', '?>'], '', $code));
    }

    protected function wrapCodeToPreserveReturnValue(string $code): string
    {
        return <<<PHP
        \$__boost_result = (function() {
            {$code}
        })();
        if (\$__boost_result !== null) {
            echo PHP_EOL . '=> ';
            var_export(\$__boost_result);
            echo PHP_EOL;
        }
        PHP;
    }
}

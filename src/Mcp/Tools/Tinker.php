<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Exception;
use Illuminate\Support\Arr;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Throwable;

class Tinker extends Tool
{
    public function description(): string
    {
        return <<<'DESCRIPTION'
Execute PHP code in the Laravel application context, like artisan tinker.
Use this for debugging issues, checking if functions exist, and testing code snippets.
You should not create models directly without explicit user approval. Prefer Unit/Feature tests using factories for functionality testing. Prefer existing artisan commands over custom tinker code.
Returns the output of the code, as well as whatever is "returned" using "return".
DESCRIPTION;
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('code')
            ->description('PHP code to execute (without opening <?php tags)')
            ->required()
            ->integer('timeout')
            ->description('Maximum execution time in seconds (default: 180)');
    }

    /**
     * @param array<string|int> $arguments
     *
     * @throws Exception
     */
    public function handle(array $arguments): ToolResult
    {
        $code = str_replace(['<?php', '?>'], '', (string) Arr::get($arguments, 'code'));

        ini_set('memory_limit', '256M');

        ob_start();

        try {
            $result = eval($code);

            $output = ob_get_contents();

            $response = [
                'result' => $result,
                'output' => $output,
                'type' => gettype($result),
            ];

            // If a result is an object, include the class name
            if (is_object($result)) {
                $response['class'] = get_class($result);
            }

            return ToolResult::json($response);
        } catch (Throwable $e) {
            return ToolResult::json([
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

        } finally {
            ob_end_clean();
        }
    }
}

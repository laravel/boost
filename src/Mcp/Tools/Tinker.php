<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;

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
     * Handle the tool request.
     *
     * @throws Exception
     */
    public function handle(Request $request): Response
    {
        $code = str_replace(['<?php', '?>'], '', (string) $request->get('code'));

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
                $response['class'] = $result::class;
            }

            return Response::json($response);
        } catch (Throwable $throwable) {
            return Response::json([
                'error' => $throwable->getMessage(),
                'type' => $throwable::class,
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ]);

        } finally {
            ob_end_clean();
        }
    }
}

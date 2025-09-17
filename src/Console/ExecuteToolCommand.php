<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Laravel\Boost\Mcp\ToolRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class ExecuteToolCommand extends Command
{
    protected $signature = 'boost:execute-tool {tool} {arguments}';

    protected $description = 'Execute a Boost MCP tool in isolation (internal command)';

    protected $hidden = true;

    public function handle(): int
    {
        $toolClass = $this->argument('tool');
        $argumentsEncoded = $this->argument('arguments');

        // Validate the tool is registered
        if (! ToolRegistry::isToolAllowed($toolClass)) {
            $this->error("Tool not registered or not allowed: {$toolClass}");

            return 1;
        }

        // Decode arguments
        $arguments = json_decode(base64_decode($argumentsEncoded), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid arguments format: '.json_last_error_msg());

            return 1;
        }

        try {
            // Execute the tool
            $tool = app($toolClass);

            $request = new Request($arguments ?? []);
            $response = $tool->handle($request);

            // Output the result as JSON for the parent process
            echo json_encode([
                'isError' => $response->isError(),
                'content' => (string) $response->content(),
            ]);

            return 0;

        } catch (\Throwable $e) {
            // Output error result
            $errorResult = Response::error("Tool execution failed (E_THROWABLE): {$e->getMessage()}");
            $this->error(json_encode([
                'isError' => true,
                'content' => (string) $errorResult->content(),
            ]));

            return 1;
        }
    }
}

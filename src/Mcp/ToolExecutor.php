<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp;

use Dotenv\Dotenv;
use Illuminate\Support\Env;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ReflectionClass;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class ToolExecutor
{
    public function execute(string $toolClass, array $arguments = []): Response
    {
        if (! ToolRegistry::isToolAllowed($toolClass)) {
            return Response::error("Tool not registered or not allowed: {$toolClass}");
        }

        if ($this->shouldExecuteInProcess($toolClass)) {
            return $this->executeInProcess($toolClass, $arguments);
        }

        return $this->executeInSubprocess($toolClass, $arguments);
    }

    protected function shouldExecuteInProcess(string $toolClass): bool
    {
        if (! config('boost.ace.enabled', false)) {
            return false;
        }

        $reflection = new ReflectionClass($toolClass);

        return ! empty($reflection->getAttributes(IsReadOnly::class));
    }

    /**
     * Execute a tool in the current process. Used by both the MCP pipeline
     * and ToolSliceResolver to avoid duplicating instantiation logic.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function runInProcess(string $toolClass, array $arguments): Response
    {
        /** @var Tool $tool */
        $tool = app($toolClass);

        $request = new Request($arguments);

        return $tool->handle($request); // @phpstan-ignore-line
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function executeInProcess(string $toolClass, array $arguments): Response
    {
        try {
            return $this->runInProcess($toolClass, $arguments);
        } catch (Throwable $e) {
            return Response::error("Tool execution failed: {$e->getMessage()}");
        }
    }

    protected function executeInSubprocess(string $toolClass, array $arguments): Response
    {
        $command = $this->buildCommand($toolClass, $arguments);

        // We need to 'unset' env vars that will be passed from the parent process to the child process, stopping the child process from reading .env and getting updated values
        $env = (Dotenv::create(
            Env::getRepository(),
            app()->environmentPath(),
            app()->environmentFile()
        ))->safeLoad();

        $cleanEnv = array_fill_keys(array_keys($env), false);

        $process = new Process(
            command: $command,
            env: $cleanEnv,
            timeout: $this->getTimeout($arguments)
        );

        try {
            $process->mustRun();

            $output = $process->getOutput();
            $decoded = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return Response::error('Invalid JSON output from tool process: '.json_last_error_msg());
            }

            return $this->reconstructResponse($decoded);
        } catch (ProcessTimedOutException) {
            $process->stop();

            return Response::error("Tool execution timed out after {$this->getTimeout($arguments)} seconds");

        } catch (ProcessFailedException) {
            $errorOutput = $process->getErrorOutput().$process->getOutput();

            return Response::error("Process tool execution failed: {$errorOutput}");
        }
    }

    protected function getTimeout(array $arguments): int
    {
        $timeout = (int) ($arguments['timeout'] ?? 180);

        return max(1, min(600, $timeout));
    }

    /**
     * Reconstruct a Response from JSON data.
     *
     * @param  array<string, mixed>  $data
     */
    protected function reconstructResponse(array $data): Response
    {
        if (! isset($data['isError']) || ! isset($data['content'])) {
            return Response::error('Invalid tool response format.');
        }

        if ($data['isError']) {
            $errorText = 'Unknown error';

            if (is_array($data['content']) && ! empty($data['content'])) {
                $firstContent = $data['content'][0] ?? [];

                if (is_array($firstContent)) {
                    $errorText = $firstContent['text'] ?? $errorText;
                }
            }

            return Response::error($errorText);
        }

        // Handle array format - extract text content
        if (is_array($data['content']) && ! empty($data['content'])) {
            $firstContent = $data['content'][0] ?? [];

            if (is_array($firstContent)) {
                $text = $firstContent['text'] ?? '';

                $decoded = json_decode((string) $text, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return Response::json($decoded);
                }

                return Response::text($text);
            }
        }

        return Response::text('');
    }

    /**
     * Build the command array for executing a tool in a subprocess.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string>
     */
    protected function buildCommand(string $toolClass, array $arguments): array
    {
        return [
            PHP_BINARY,
            base_path('artisan'),
            'boost:execute-tool',
            $toolClass,
            base64_encode(json_encode($arguments)),
        ];
    }
}

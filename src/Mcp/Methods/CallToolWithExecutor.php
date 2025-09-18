<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Methods;

use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\Errable;
use Laravel\Mcp\Server\Contracts\Method;
use Laravel\Mcp\Server\Exceptions\JsonRpcException;
use Laravel\Mcp\Server\Methods\Concerns\InteractsWithResponses;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;
use Throwable;

class CallToolWithExecutor implements Method, Errable
{
    use InteractsWithResponses;

    public function __construct(protected ToolExecutor $executor)
    {
        //
    }

    /**
     * Handle the JSON-RPC tool/call request with process isolation.
     */
    public function handle(JsonRpcRequest $request, ServerContext $context): JsonRpcResponse
    {
        if (is_null($request->get('name'))) {
            throw new JsonRpcException(
                'Missing [name] parameter.',
                -32602,
                $request->id,
            );
        }

        $tool = $context
            ->tools($request->toRequest())
            ->first(
                fn ($tool): bool => $tool->name() === $request->params['name'],
                fn () => throw new JsonRpcException(
                    "Tool [{$request->params['name']}] not found.",
                    -32602,
                    $request->id,
                ));

        $arguments = [];
        if (isset($request->params['arguments']) && is_array($request->params['arguments'])) {
            $arguments = $request->params['arguments'];
        }

        try {
            $response = $this->executor->execute(get_class($tool), $arguments);
        } catch (Throwable $e) {
            $response = Response::error('Tool execution error: '.$e->getMessage());
        }

        return $this->toJsonRpcResponse($request, $response, fn ($responses) => [
            'content' => $responses->map(fn ($response) => $response->content()->toTool($tool))->all(),
            'isError' => $responses->contains(fn ($response) => $response->isError()),
        ]);
    }
}

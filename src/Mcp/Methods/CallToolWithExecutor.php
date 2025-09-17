<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Methods;

use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Mcp\Server\Contracts\Errable;
use Laravel\Mcp\Server\Contracts\Method;
use Laravel\Mcp\Server\Exceptions\JsonRpcException;
use Laravel\Mcp\Server\Methods\Concerns\InteractsWithResponses;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;

class CallToolWithExecutor implements Method, Errable
{
    use InteractsWithResponses;

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

        $executor = app(ToolExecutor::class);

        $arguments = [];
        if (isset($request->params['arguments']) && is_array($request->params['arguments'])) {
            $arguments = $request->params['arguments'];
        }

        $response = $executor->execute(get_class($tool), $arguments);

        return $this->toJsonRpcResponse($request, $response, fn ($responses) => [
            'content' => $responses->map(fn ($response) => $response->content()->toTool($tool))->all(),
            'isError' => $responses->contains(fn ($response) => $response->isError()),
        ]);
    }
}

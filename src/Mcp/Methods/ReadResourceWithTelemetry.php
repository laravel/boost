<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Methods;

use Generator;
use Illuminate\Container\Container;
use Laravel\Boost\Telemetry\TelemetryCollector;
use Laravel\Mcp\Server\Methods\ReadResource;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;

class ReadResourceWithTelemetry extends ReadResource
{
    /**
     * @return Generator<JsonRpcResponse>|JsonRpcResponse
     */
    public function handle(JsonRpcRequest $request, ServerContext $context): Generator|JsonRpcResponse
    {
        $uri = (string) $request->get('uri');

        try {
            return parent::handle($request, $context);
        } finally {
            if (config('boost.telemetry.enabled')) {
                Container::getInstance()->make(TelemetryCollector::class)->recordResource($uri);
            }
        }
    }
}

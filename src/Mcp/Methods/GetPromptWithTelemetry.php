<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Methods;

use Generator;
use Laravel\Boost\Telemetry\TelemetryCollector;
use Laravel\Mcp\Server\Methods\GetPrompt;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;

class GetPromptWithTelemetry extends GetPrompt
{
    /**
     * @return Generator<JsonRpcResponse>|JsonRpcResponse
     */
    public function handle(JsonRpcRequest $request, ServerContext $context): Generator|JsonRpcResponse
    {
        $promptName = (string) $request->get('name');

        try {
            return parent::handle($request, $context);
        } finally {
            if (config('boost.telemetry.enabled')) {
                app(TelemetryCollector::class)->recordPrompt($promptName);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Ace;

use Illuminate\Contracts\Container\Container;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;

class ToolSliceResolver
{
    public function __construct(
        protected Container $container,
        protected ToolExecutor $executor,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function resolve(ContextSlice $slice, array $params = []): SliceResult
    {
        if ($slice->toolClass === null) {
            return new SliceResult($slice->id, '', isError: true);
        }

        try {
            $response = $this->executor->runInProcess($slice->toolClass, $params);

            /** @var Tool $tool */
            $tool = $this->container->make($slice->toolClass);

            $content = $this->extractResponseText($response, $tool);

            return new SliceResult($slice->id, $content, isError: $response->isError());
        } catch (Throwable $e) {
            return new SliceResult($slice->id, "Error resolving '{$slice->id}': {$e->getMessage()}", isError: true);
        }
    }

    protected function extractResponseText(Response $response, Tool $tool): string
    {
        $contentObj = $response->content();
        $toolArray = $contentObj->toTool($tool);

        return $toolArray['text'] ?? '';
    }
}

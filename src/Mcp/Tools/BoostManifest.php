<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Laravel\Boost\Mcp\Ace\Manifest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class BoostManifest extends Tool
{
    protected string $description = 'Get the manifest of all available context slices and bundles. Returns a compact catalog (~200 tokens) of what context you can load with resolve-context. Call this first to see what is available.';

    public function __construct(protected Manifest $manifest) {}

    public function handle(Request $request): Response
    {
        return Response::text($this->manifest->render());
    }
}

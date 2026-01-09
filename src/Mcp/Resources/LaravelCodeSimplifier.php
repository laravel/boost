<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Resources;

use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class LaravelCodeSimplifier extends Resource
{
    use RendersBladeGuidelines;

    protected string $description = 'Simplifies and refines PHP/Laravel code for clarity, consistency, and maintainability while preserving all functionality. Focuses on recently modified code unless instructed otherwise.';

    protected string $uri = 'file://instructions/laravel-code-simplifier.md';

    protected string $mimeType = 'text/markdown';

    public function handle(): Response
    {
        $bladePath = dirname(__DIR__, 3).'/resources/guidelines/laravel-code-simplifier.blade.php';
        $content = $this->renderGuidelineFile($bladePath);

        return Response::text($content);
    }
}

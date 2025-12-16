<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Resources;

use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class PackageGuidelineResource extends Resource
{
    use RendersBladeGuidelines;

    public function __construct(
        protected string $packageName,
        protected string $bladePath,
    ) {
        $this->name = $this->packageName;
        $this->title = $this->packageName;
        $this->uri = "file://instructions/{$packageName}.md";
        $this->description = "Guidelines for {$packageName}";
        $this->mimeType = 'text/markdown';
    }

    public function handle(): Response
    {
        $content = $this->renderGuidelineFile($this->bladePath);

        return Response::text($content);
    }
}

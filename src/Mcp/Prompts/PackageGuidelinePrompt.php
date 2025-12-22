<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts;

use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

class PackageGuidelinePrompt extends Prompt
{
    use RendersBladeGuidelines;

    public function __construct(
        protected string $packageName,
        protected string $bladePath,
    ) {
        $this->name = $this->packageName;
        $this->title = $this->packageName;
        $this->description = "Guidelines for {$packageName}";
    }

    public function handle(): Response
    {
        $content = $this->renderGuidelineFile($this->bladePath);

        return Response::text($content);
    }
}

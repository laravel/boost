<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Resources;

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class ThirdPartyResource extends Resource
{
    use RendersBladeGuidelines;

    public function __construct(
        protected GuidelineAssist $guidelineAssist,
        protected string $packageName,
        protected string $bladePath,
    ) {
        $this->uri = "file://instructions/{$packageName}.md";
        $this->description = "Guidelines for {$packageName}";
        $this->mimeType = 'text/markdown';
    }

    public function handle(): Response
    {
        $content = $this->renderBlade($this->bladePath);

        return Response::text($content);
    }

    protected function getGuidelineAssist(): GuidelineAssist
    {
        return $this->guidelineAssist;
    }
}

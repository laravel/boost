<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts;

use Illuminate\Support\Str;
use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

class BladePrompt extends Prompt
{
    use RendersBladeGuidelines;

    public function __construct(
        protected GuidelineAssist $guidelineAssist,
        protected string $packageName,
        protected string $bladePath,
    ) {

        $this->name = Str::slug(str_replace('/', '-', $packageName)).'-task';
        $this->description = "Guidelines for {$packageName}";
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

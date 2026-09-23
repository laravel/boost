<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts\UpgradeAiSdkv1;

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Support\PackageRegistry;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Roster\ProjectManager;

class UpgradeAiSdkV1 extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'upgrade-ai-sdk-v1';

    protected string $title = 'upgrade_ai_sdk_v1';

    protected string $description = 'Provides step-by-step guidance for upgrading the Laravel AI SDK from 0.11 to 1.0.';

    public function shouldRegister(ProjectManager $project): bool
    {
        return $project->php()->uses(PackageRegistry::AI, '<1.0.0');
    }

    public function handle(): Response
    {
        $content = $this->renderBladeFile(__DIR__.'/upgrade-ai-sdk-v1.blade.php');

        return Response::text($content);
    }
}

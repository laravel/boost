<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts\UpgradeAiv1;

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Support\PackageRegistry;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Roster\ProjectManager;

class UpgradeAiV1 extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'upgrade-ai-v1';

    protected string $title = 'upgrade_ai_v1';

    protected string $description = 'Provides step-by-step guidance for upgrading Laravel AI from 0.11 to 1.0.';

    public function shouldRegister(ProjectManager $project): bool
    {
        return $project->php()->uses(PackageRegistry::AI, '<1.0.0');
    }

    public function handle(): Response
    {
        $content = $this->renderBladeFile(__DIR__.'/upgrade-ai-v1.blade.php');

        return Response::text($content);
    }
}

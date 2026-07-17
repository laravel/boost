<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts\UpgradeInertiav3;

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Support\PackageRegistry;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Roster\ProjectManager;

class UpgradeInertiaV3 extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'upgrade-inertia-v3';

    protected string $title = 'upgrade_inertia_v3';

    protected string $description = 'Provides step-by-step guidance for upgrading from Inertia v2 to v3.';

    public function shouldRegister(ProjectManager $project): bool
    {
        if ($project->php()->uses(PackageRegistry::INERTIA_LARAVEL)) {
            return true;
        }

        if ($project->js()->uses(PackageRegistry::INERTIA_REACT)) {
            return true;
        }

        if ($project->js()->uses(PackageRegistry::INERTIA_VUE)) {
            return true;
        }

        return $project->js()->uses(PackageRegistry::INERTIA_SVELTE);
    }

    public function handle(): Response
    {
        $project = $this->getGuidelineAssist()->project;

        $content = $this->renderBladeFile(__DIR__.'/upgrade-inertia-v3.blade.php', [
            'usesReact' => $project->js()->uses(PackageRegistry::INERTIA_REACT),
            'usesVue' => $project->js()->uses(PackageRegistry::INERTIA_VUE),
            'usesSvelte' => $project->js()->uses(PackageRegistry::INERTIA_SVELTE),
        ]);

        return Response::text($content);
    }
}

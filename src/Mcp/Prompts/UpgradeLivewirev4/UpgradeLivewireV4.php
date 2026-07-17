<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts\UpgradeLivewirev4;

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Support\PackageRegistry;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Roster\ProjectManager;

class UpgradeLivewireV4 extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'upgrade-livewire-v4';

    protected string $title = 'upgrade_livewire_v4';

    protected string $description = 'Provides step-by-step guidance for upgrading from Livewire v3 to v4.';

    public function shouldRegister(ProjectManager $project): bool
    {
        return $project->php()->uses(PackageRegistry::LIVEWIRE);
    }

    public function handle(): Response
    {
        $content = $this->renderBladeFile(__DIR__.'/upgrade-livewire-v4.blade.php');

        return Response::text($content);
    }
}

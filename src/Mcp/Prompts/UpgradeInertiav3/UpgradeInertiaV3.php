<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts\UpgradeInertiav3;

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;

class UpgradeInertiaV3 extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'upgrade-inertia-v3';

    protected string $title = 'upgrade_inertia_v3';

    protected string $description = 'Provides step-by-step guidance for upgrading from Inertia v2 to v3.';

    public function shouldRegister(Roster $roster): bool
    {
        return $roster->uses(Packages::INERTIA_LARAVEL)
            || $roster->uses(Packages::INERTIA_REACT)
            || $roster->uses(Packages::INERTIA_VUE)
            || $roster->uses(Packages::INERTIA_SVELTE);
    }

    public function handle(): Response
    {
        $roster = $this->getGuidelineAssist()->roster;

        $content = $this->renderBladeFile(__DIR__.'/upgrade-inertia-v3.blade.php', [
            'usesReact' => $roster->uses(Packages::INERTIA_REACT),
            'usesVue' => $roster->uses(Packages::INERTIA_VUE),
            'usesSvelte' => $roster->uses(Packages::INERTIA_SVELTE),
        ]);

        return Response::text($content);
    }
}

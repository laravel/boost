<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts\UpgradeLivewirev4;

use Laravel\Boost\Mcp\Prompts\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;

class UpgradeLivewireV4 extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'upgrade-livewire-v4';

    protected string $title = 'upgrade_livewire_v4';

    protected string $description = 'Provides step-by-step guidance for upgrading from Livewire v3 to v4, including breaking changes, new features, and migration instructions.';

    public function shouldRegister(Roster $roster): bool
    {
        return $roster->uses(Packages::LIVEWIRE);
    }

    public function handle(): Response
    {
        $instructions = $this->renderGuidelineFile(__DIR__.'/upgrade-livewire-v4.blade.php');
        $referenceGuide = $this->renderGuidelineFile(__DIR__.'/livewire-v4-reference-guide.blade.php');

        $content = $instructions."\n\n".$referenceGuide;

        return Response::text($content);
    }
}

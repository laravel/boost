<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts\UpgradeLaravelv13;

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Install\Herd;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;

class UpgradeLaravelV13 extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'upgrade-laravel-v13';

    protected string $title = 'upgrade_laravel_v13';

    protected string $description = 'Provides step-by-step guidance for upgrading from Laravel 12.x to 13.0.';

    public function shouldRegister(Roster $roster): bool
    {
        return $roster->usesVersion(Packages::LARAVEL, '12.0.0', '>=')
            && $roster->usesVersion(Packages::LARAVEL, '13.0.0', '<');
    }

    public function handle(): Response
    {
        $content = $this->renderBladeFile(__DIR__.'/upgrade-laravel-v13.blade.php', [
            'usesHerd' => app(Herd::class)->isInstalled(),
        ]);

        return Response::text($content);
    }
}

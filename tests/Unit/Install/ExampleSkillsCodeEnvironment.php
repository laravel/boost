<?php

declare(strict_types=1);

namespace Tests\Unit\Install;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\SkillsAgent;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\Enums\Platform;

class ExampleSkillsCodeEnvironment extends CodeEnvironment implements Agent, SkillsAgent
{
    public function name(): string
    {
        return 'example-skills';
    }

    public function displayName(): string
    {
        return 'Example Skills IDE';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return ['command' => 'which example-skills'];
    }

    public function projectDetectionConfig(): array
    {
        return ['paths' => ['.example-skills']];
    }

    public function guidelinesPath(): string
    {
        return 'EXAMPLE_SKILLS.md';
    }

    public function skillsPath(): string
    {
        return '.example-skills/skills';
    }
}

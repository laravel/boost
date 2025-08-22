<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Install\Enums\Platform;

class UniversalAgentConfig extends CodeEnvironment implements Agent
{
    public function name(): string
    {
        return 'universal';
    }

    public function displayName(): string
    {
        return 'AGENTS.md - Universal Config ';
    }

    public function guidelinesPath(): string
    {
        return 'AGENTS.md';
    }

     public function systemDetectionConfig(Platform $platform): array
     {
        return [
            'files' => [],
        ];
    }

    public function projectDetectionConfig(): array
    {
        return [
            'files' => [],
        ];
    }

    public function detectOnSystem(Platform $platform): bool
    {
        return false;
    }
}

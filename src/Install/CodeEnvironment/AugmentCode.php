<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Install\Enums\Platform;

class AugmentCode extends CodeEnvironment implements Agent
{
    public function name(): string
    {
        return 'augment';
    }

    public function displayName(): string
    {
        return 'Augment Code';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        // Detect Auggie CLI installed globally via npm
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'which auggie',
            ],
            Platform::Windows => [
                'command' => 'where auggie 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.augment'],
            'files' => ['.augment/rules/guidelines.md'],
        ];
    }

    public function detectOnSystem(Platform $platform): bool
    {
        // Use parent's detection logic which will run the command
        return parent::detectOnSystem($platform);
    }

    public function mcpClientName(): ?string
    {
        return null;
    }

    public function guidelinesPath(): string
    {
        return '.augment/rules/guidelines.md';
    }
}

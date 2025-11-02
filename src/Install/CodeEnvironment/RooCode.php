<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Install\Enums\Platform;

class RooCode extends CodeEnvironment implements Agent
{
    public function name(): string
    {
        return 'roocode';
    }

    public function displayName(): string
    {
        return 'RooCode';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        // RooCode is a VSCode extension, not a standalone app
        return [
            'files' => [],
        ];
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.roo'],
        ];
    }

    public function detectOnSystem(Platform $platform): bool
    {
        return false;
    }

    public function mcpClientName(): ?string
    {
        return null;
    }

    public function guidelinesPath(): string
    {
        return '.roo/rules/rules.md';
    }

    public function frontmatter(): bool
    {
        return false;
    }
}


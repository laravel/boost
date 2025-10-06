<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Install\Enums\Platform;

class Copilot extends CodeEnvironment implements Agent
{
    public function name(): string
    {
        return 'copilot';
    }

    public function displayName(): string
    {
        return 'GitHub Copilot';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        // Copilot doesn't have system-wide detection as it's an extension/feature
        return [
            'files' => [],
        ];
    }

    public function projectDetectionConfig(): array
    {
        return [
            'files' => ['.github/instructions/laravel-boost.instructions.md'],
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
        return '.github/instructions/laravel-boost.instructions.md';
    }

    public function frontmatter(): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Guideline;
use Laravel\Boost\Install\Enums\Platform;

class Copilot extends Agent implements Guideline
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
            'files' => ['.github/copilot-instructions.md'],
        ];
    }

    public function detectOnSystem(Platform $platform): bool
    {
        return false;
    }

    public function guidelinesPath(): string
    {
        return '.github/copilot-instructions.md';
    }
}

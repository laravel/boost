<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Install\Enums\Platform;

class Roocode extends CodeEnvironment implements Agent
{
    public function name(): string
    {
        return 'roocode';
    }

    public function displayName(): string
    {
        return 'Roocode';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        // Roocode doesn't have system-wide detection as it's a VS Code extension
        return [
            'files' => [],
        ];
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.roo'],
            'files' => ['.roorules'],
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
        return '.roo/rules/laravel-boost.md';
    }
}

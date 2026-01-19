<?php

declare(strict_types=1);

namespace Tests\Unit\Install;

use Laravel\Boost\Contracts\SupportGuidelines;
use Laravel\Boost\Contracts\SupportMCP;
use Laravel\Boost\Install\Agent\Agent;
use Laravel\Boost\Install\Enums\Platform;

class ExampleAgent extends Agent implements SupportGuidelines, SupportMCP
{
    public function name(): string
    {
        return 'example';
    }

    public function displayName(): string
    {
        return 'Example IDE';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return ['command' => 'which example'];
    }

    public function projectDetectionConfig(): array
    {
        return ['paths' => ['.example']];
    }

    public function mcpConfigPath(): string
    {
        return '.example/config.json';
    }

    public function guidelinesPath(): string
    {
        return 'EXAMPLE.md';
    }
}

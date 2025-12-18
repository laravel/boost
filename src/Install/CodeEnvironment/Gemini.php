<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\Platform;

class Gemini extends CodeEnvironment implements Agent, McpClient
{
    public function name(): string
    {
        return 'gemini';
    }

    public function displayName(): string
    {
        return 'Gemini';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v gemini',
            ],
            Platform::Windows => [
                'command' => 'where gemini 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.gemini'],
            'files' => ['GEMINI.md'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.gemini/settings.json';
    }

    public function guidelinesPath(): string
    {
        return config('boost.code_environments.gemini.guidelines_path', 'GEMINI.md');
    }
}

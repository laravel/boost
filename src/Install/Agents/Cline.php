<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class Cline extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'cline';
    }

    public function displayName(): string
    {
        return 'CLINE';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Visual Studio Code.app'],
            ],
            Platform::Linux => [
                'command' => 'command -v code',
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Microsoft VS Code',
                    '%LOCALAPPDATA%\\Programs\\Microsoft VS Code',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.cline'],
            'files' => ['AGENTS.md'],
        ];
    }

    public function mcpConfigPath(): string
    {
        

        return $this->getMcpConfigPath();
    }

    public function mcpConfigKey(): string
    {
        return 'mcpServers';
    }

    /** {@inheritDoc} */
    public function mcpServerConfig(string $command, array $args = [], array $env = []): array
    {
        return [
            'autoApprove' => [],
            'timeout' => 300,
            'type' => 'stdio',
            'disabled' => false,
            'command' => $command,
            'args' => [...$args],
            'cwd' => base_path(),
            'env' => $env,
        ];
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.cline.guidelines_path', 'AGENTS.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.cline.skills_path', '.cline/skills');
    }

    public function getMcpConfigPath(): string
    {
        //Ensure that there are no null entries in cline_mcp_settings.json
        $home = getenv('HOME');
        $platform = Platform::current();
        return match ($platform) {
            Platform::Darwin => $home.'/Library/Application Support/Code/User/globalStorage/saoudrizwan.claude-dev/settings/cline_mcp_settings.json',
            Platform::Linux => $home.'/.config/Code/User/globalStorage/saoudrizwan.claude-dev/settings/cline_mcp_settings.json',
            Platform::Windows => $home.'\AppData\Roaming\Code\User\globalStorage\saoudrizwan.claude-dev\settings\cline_mcp_settings.json',
        };    
    }
}

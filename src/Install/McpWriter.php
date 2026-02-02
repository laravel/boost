<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Boost\Contracts\SupportsMcp;
use RuntimeException;

class McpWriter
{
    public const SUCCESS = 0;

    public function __construct(protected SupportsMcp $agent)
    {
        //
    }

    public function write(?Sail $sail = null, ?Ddev $ddev = null, ?Herd $herd = null): int
    {
        $this->installBoostMcp($sail, $ddev);

        if ($herd instanceof Herd) {
            $this->installHerdMcp($herd);
        }

        return self::SUCCESS;
    }

    protected function installBoostMcp(?Sail $sail, ?Ddev $ddev): void
    {
        $mcp = $this->buildBoostMcpCommand($sail, $ddev);

        if (! $this->agent->installMcp($mcp['key'], $mcp['command'], $mcp['args'])) {
            throw new RuntimeException('Failed to install Boost MCP: could not write configuration');
        }
    }

    /**
     * @return array{key: string, command: string, args: array<int, string>}
     */
    protected function buildBoostMcpCommand(?Sail $sail, ?Ddev $ddev): array
    {
        if ($sail instanceof Sail) {
            return $sail->buildMcpCommand('laravel-boost');
        }

        if ($ddev instanceof Ddev) {
            return $ddev->buildMcpCommand('laravel-boost');
        }

        if ($this->isRunningInsideWsl()) {
            return [
                'key' => 'laravel-boost',
                'command' => 'wsl.exe',
                'args' => [$this->agent->getPhpPath(true), $this->agent->getArtisanPath(true), 'boost:mcp'],
            ];
        }

        return [
            'key' => 'laravel-boost',
            'command' => $this->agent->getPhpPath(),
            'args' => [$this->agent->getArtisanPath(), 'boost:mcp'],
        ];
    }

    private function isRunningInsideWsl(): bool
    {
        return ! empty(getenv('WSL_DISTRO_NAME')) || ! empty(getenv('IS_WSL'));
    }

    protected function installHerdMcp(Herd $herd): void
    {
        $installed = $this->agent->installMcp(
            key: 'herd',
            command: $this->agent->getPhpPath(),
            args: [$herd->mcpPath()],
            env: ['SITE_PATH' => base_path()]
        );

        if (! $installed) {
            throw new RuntimeException('Failed to install Herd MCP: could not write configuration');
        }
    }
}

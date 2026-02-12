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

    public function write(?Sail $sail = null, ?Herd $herd = null): int
    {
        $this->installBoostMcp($sail);

        if ($herd instanceof Herd) {
            $this->installHerdMcp($herd);
        }

        return self::SUCCESS;
    }

    protected function installBoostMcp(?Sail $sail): void
    {
        $mcp = $this->buildBoostMcpCommand($sail);

        $env = $mcp['env'] ?? [];
        $cwd = $mcp['cwd'] ?? null;

        if ($cwd !== null) {
            config()->set('boost.mcp.server.cwd', $cwd);
        }

        if (! $this->agent->installMcp(
            $mcp['key'],
            $mcp['command'],
            $mcp['args'],
            $env,
            $mcp['cwd'] ?? null
        )) {
            throw new RuntimeException('Failed to install Boost MCP: could not write configuration');
        }
    }

    /**
     * @return array{
     *     key: string,
     *     command: string,
     *     args: array<int, string>,
     *     env?: array<string, string>|null,
     *     cwd?: string|null
     * }
     */
    protected function buildBoostMcpCommand(?Sail $sail): array
    {
        $custom = config('boost.mcp.server');

        if (! empty($custom['command'])) {
            return [
                'key' => 'laravel-boost',
                'command' => $custom['command'],
                'args' => $custom['args'] ?? [],
                'env' => $custom['env'] ?? null,
                'cwd' => $custom['cwd'] ?? null,
            ];
        }

        if ($sail instanceof Sail) {
            return $sail->buildMcpCommand('laravel-boost');
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

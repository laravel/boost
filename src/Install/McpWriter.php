<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Laravel\Boost\Contracts\SupportsMcp;
use RuntimeException;

class McpWriter
{
    public const SUCCESS = 0;

    /** @var array<int, string> First-party server keys that cannot be overridden */
    private const FIRST_PARTY_KEYS = ['laravel-boost', 'nightwatch'];

    public function __construct(protected SupportsMcp $agent)
    {
        //
    }

    /**
     * @param  Collection<int, McpServer>|null  $thirdPartyServers
     */
    public function write(?Sail $sail = null, ?Nightwatch $nightwatch = null, ?Collection $thirdPartyServers = null): int
    {
        $this->installBoostMcp($sail);

        if ($nightwatch instanceof Nightwatch) {
            $this->installNightwatchMcp($nightwatch);
        }

        if ($thirdPartyServers !== null) {
            $this->installThirdPartyServers($thirdPartyServers);
        }

        return self::SUCCESS;
    }

    protected function installBoostMcp(?Sail $sail): void
    {
        $mcp = $this->buildBoostMcpCommand($sail);

        if (! $this->agent->installMcp($mcp['key'], $mcp['command'], $mcp['args'])) {
            throw new RuntimeException('Failed to install Boost MCP: could not write configuration');
        }
    }

    /**
     * @return array{key: string, command: string, args: array<int, string>}
     */
    protected function buildBoostMcpCommand(?Sail $sail): array
    {
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

    protected function installNightwatchMcp(Nightwatch $nightwatch): void
    {
        if (! $this->agent->installHttpMcp('nightwatch', $nightwatch->mcpUrl())) {
            throw new RuntimeException('Failed to install Nightwatch MCP: could not write configuration');
        }
    }

    /**
     * @param  Collection<int, McpServer>  $servers
     */
    protected function installThirdPartyServers(Collection $servers): void
    {
        foreach ($servers as $server) {
            if (in_array($server->name, self::FIRST_PARTY_KEYS, true)) {
                $this->warn("Skipping third-party MCP server '{$server->name}': conflicts with a first-party server key.");

                continue;
            }

            if ($server->url !== null && $server->command === null) {
                $success = $this->agent->installHttpMcp($server->name, $server->url);
            } else {
                $success = $this->agent->installMcp($server->name, (string) $server->command, $server->args, $server->env ?: []);
            }

            if (! $success) {
                throw new RuntimeException("Failed to install third-party MCP server '{$server->name}': could not write configuration");
            }
        }
    }

    protected function warn(string $message): void
    {
        // Warnings are surfaced via the InstallCommand; this is a no-op by default
        // but can be overridden in tests or subclasses.
    }
}

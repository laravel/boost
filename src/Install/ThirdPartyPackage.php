<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Laravel\Boost\Support\Composer;

class ThirdPartyPackage
{
    /** @var Collection<int, McpServer> */
    private Collection $mcpServersCollection;

    /** @var array<int, string> */
    private array $warningMessages = [];

    public function __construct(
        public readonly string $name,
        public readonly bool $hasGuidelines,
        public readonly bool $hasSkills,
        public readonly bool $hasMcp = false,
    ) {
        $this->mcpServersCollection = collect();
    }

    /**
     * Discover all third-party packages with boost features.
     *
     * @return Collection<string, ThirdPartyPackage>
     */
    public static function discover(): Collection
    {
        $withGuidelines = Composer::packagesDirectoriesWithBoostGuidelines();
        $withSkills = Composer::packagesDirectoriesWithBoostSkills();
        $withMcp = Composer::packagesDirectoriesWithBoostMcp();

        $allPackageNames = array_unique(array_merge(
            array_keys($withGuidelines),
            array_keys($withSkills),
            array_keys($withMcp),
        ));

        return collect($allPackageNames)
            ->reject(fn (string $name): bool => Composer::isFirstPartyPackage($name))
            ->mapWithKeys(function (string $name) use ($withGuidelines, $withSkills, $withMcp): array {
                $warnings = [];
                $servers = collect();

                if (isset($withMcp[$name])) {
                    [$servers, $warnings] = self::parseMcpJson($name, $withMcp[$name]);
                }

                $package = new self(
                    name: $name,
                    hasGuidelines: isset($withGuidelines[$name]),
                    hasSkills: isset($withSkills[$name]),
                    hasMcp: $servers->isNotEmpty(),
                );

                $package->mcpServersCollection = $servers;
                $package->warningMessages = $warnings;

                return [$name => $package];
            });
    }

    /**
     * @return array{0: Collection<int, McpServer>, 1: array<int, string>}
     */
    private static function parseMcpJson(string $packageName, string $filePath): array
    {
        $warnings = [];
        $servers = collect();

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $warnings[] = "[{$packageName}] Invalid JSON in mcp.json: ".json_last_error_msg();

            return [$servers, $warnings];
        }

        if (! isset($data['servers']) || ! is_array($data['servers'])) {
            $warnings[] = "[{$packageName}] mcp.json must contain a top-level 'servers' array";

            return [$servers, $warnings];
        }

        foreach ($data['servers'] as $entry) {
            try {
                $servers->push(McpServer::fromArray($entry));
            } catch (InvalidArgumentException $e) {
                $warnings[] = "[{$packageName}] Skipping server entry: ".$e->getMessage();
            }
        }

        return [$servers, $warnings];
    }

    /** @return Collection<int, McpServer> */
    public function mcpServers(): Collection
    {
        return $this->mcpServersCollection;
    }

    /** @return array<int, string> */
    public function warnings(): array
    {
        return $this->warningMessages;
    }

    public function featureLabel(): string
    {
        return match (true) {
            $this->hasGuidelines && $this->hasSkills && $this->hasMcp => 'guidelines, skills, mcp',
            $this->hasGuidelines && $this->hasSkills => 'guidelines, skills',
            $this->hasGuidelines && $this->hasMcp => 'guidelines, mcp',
            $this->hasSkills && $this->hasMcp => 'skills, mcp',
            $this->hasGuidelines => 'guideline',
            $this->hasSkills => 'skills',
            $this->hasMcp => 'mcp',
            default => '',
        };
    }

    public function displayLabel(): string
    {
        return "{$this->name} ({$this->featureLabel()})";
    }
}

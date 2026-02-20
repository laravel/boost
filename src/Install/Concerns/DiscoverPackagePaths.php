<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Concerns;

use Illuminate\Support\Collection;
use Laravel\Boost\Support\Composer;
use Laravel\Boost\Support\Npm;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;

trait DiscoverPackagePaths
{
    /**
     * Only include guidelines for these package names if they're a direct requirement.
     * This fixes every Boost user getting the MCP guidelines due to indirect import.
     *
     * @var array<int, Packages>
     * */
    protected array $mustBeDirect = [
        Packages::MCP,
        Packages::LIVEWIRE,
    ];

    /**
     * Packages excluded from Roster-based guideline discovery.
     * Boost is already loaded by getCoreGuidelines(); Sail requires explicit opt-in.
     *
     * @var array<int, Packages>
     */
    protected array $excludedPackages = [
        Packages::BOOST,
        Packages::SAIL,
    ];

    abstract protected function getRoster(): Roster;

    /**
     * Package priority system to handle conflicts between packages.
     * When a higher-priority package is present, lower-priority packages are excluded from guidelines.
     */
    protected function getPackagePriorities(): array
    {
        return [
            Packages::PEST->value => [Packages::PHPUNIT->value],
            Packages::FLUXUI_PRO->value => [Packages::FLUXUI_FREE->value],
        ];
    }

    protected function shouldExcludePackage(Package $package): bool
    {
        if (in_array($package->package(), $this->excludedPackages, true)) {
            return true;
        }

        foreach ($this->getPackagePriorities() as $priorityPackage => $excludedPackages) {
            if (in_array($package->package()->value, $excludedPackages, true)
                && $this->getRoster()->uses(Packages::from($priorityPackage))) {
                return true;
            }
        }

        return $package->indirect() && in_array($package->package(), $this->mustBeDirect, true);
    }

    /**
     * @return Collection<int, array{path: string, name: string, version: string}>
     */
    protected function discoverPackagePaths(string $basePath): Collection
    {
        $packages = $this->getRoster()->packages()
            ->reject(fn (Package $package): bool => $this->shouldExcludePackage($package));

        /** @var Collection<int, array{path: string, name: string, version: string}> $result */
        $result = $packages
            ->map(function (Package $package) use ($basePath): array {
                $name = $this->normalizePackageName($package->name());

                return [
                    'path' => $basePath.DIRECTORY_SEPARATOR.$name,
                    'name' => $name,
                    'version' => $package->majorVersion(),
                ];
            })
            ->collect();

        return $result->filter(fn (array $package): bool => is_dir($package['path']));
    }

    protected function normalizePackageName(string $name): string
    {
        return str_replace('_', '-', strtolower($name));
    }

    protected function getBoostAiPath(): string
    {
        return __DIR__.'/../../../.ai';
    }

    protected function resolveFirstPartyBoostPath(Package $package, string $subpath): ?string
    {
        if (! Composer::isFirstPartyPackage($package->rawName()) && ! Npm::isFirstPartyPackage($package->rawName())) {
            return null;
        }

        $path = implode(DIRECTORY_SEPARATOR, [$package->path(), 'resources', 'boost', $subpath]);

        return is_dir($path) ? $path : null;
    }
}

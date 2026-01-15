<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Concerns;

use Illuminate\Support\Collection;
use Laravel\Roster\Roster;

trait DiscoverPackagePaths
{
    abstract protected function getRoster(): Roster;

    /**
     * @return Collection<int, array{path: string, name: string, version: string}>
     */
    protected function discoverPackagePaths(string $basePath): Collection
    {
        /** @var Collection<int, array{path: string, name: string, version: string}> $packages */
        $packages = $this->getRoster()->packages()
            ->map(function ($package) use ($basePath): array {
                $name = $this->normalizePackageName($package->name());

                return [
                    'path' => $basePath.DIRECTORY_SEPARATOR.$name,
                    'name' => $name,
                    'version' => $package->majorVersion(),
                ];
            })
            ->collect();

        return $packages->filter(fn (array $package): bool => is_dir($package['path']));
    }

    protected function normalizePackageName(string $name): string
    {
        return str_replace('_', '-', strtolower($name));
    }

    protected function getBoostAiPath(): string
    {
        return __DIR__.'/../../../.ai';
    }
}

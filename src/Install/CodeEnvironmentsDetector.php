<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\Enums\Platform;

class CodeEnvironmentsDetector
{
    public function __construct(
        private readonly Container $container,
        private readonly BoostManager $boostManager
    ) {}

    /**
     * Detect installed applications on the current platform.
     *
     * @return array<string>
     */
    public function discoverSystemInstalledCodeEnvironments(): array
    {
        $platform = Platform::current();

        return $this->getCodeEnvironments()
            ->filter(fn (CodeEnvironment $program): bool => $program->detectOnSystem($platform))
            ->map(fn (CodeEnvironment $program): string => $program->name())
            ->values()
            ->toArray();
    }

    /**
     * Detect applications used in the current project.
     *
     * @return array<string>
     */
    public function discoverProjectInstalledCodeEnvironments(string $basePath): array
    {
        return $this->getCodeEnvironments()
            ->filter(fn (CodeEnvironment $program): bool => $program->detectInProject($basePath))
            ->map(fn (CodeEnvironment $program): string => $program->name())
            ->values()
            ->toArray();
    }

    /**
     * Get all registered code environments.
     *
     * @return Collection<string, CodeEnvironment>
     */
    public function getCodeEnvironments(): Collection
    {
        return collect($this->boostManager->getCodeEnvironments())
            ->map(fn (string $className) => $this->container->make($className));
    }
}

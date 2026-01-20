<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\Enums\Platform;

class AgentsDetector
{
    public function __construct(
        private readonly Container $container,
        private readonly BoostManager $boostManager
    ) {}

    /**
     * Detect installed agents on the current platform.
     *
     * @return array<string>
     */
    public function discoverSystemInstalledAgents(): array
    {
        $platform = Platform::current();

        return $this->getAgents()
            ->filter(fn (Agent $program): bool => $program->detectOnSystem($platform))
            ->map(fn (Agent $program): string => $program->name())
            ->values()
            ->toArray();
    }

    /**
     * Detect agents used in the current project.
     *
     * @return array<string>
     */
    public function discoverProjectInstalledAgents(string $basePath): array
    {
        return $this->getAgents()
            ->filter(fn (Agent $program): bool => $program->detectInProject($basePath))
            ->map(fn (Agent $program): string => $program->name())
            ->values()
            ->toArray();
    }

    /**
     * Get all registered agents.
     *
     * @return Collection<string, Agent>
     */
    public function getAgents(): Collection
    {
        return collect($this->boostManager->getAgents())
            ->map(fn (string $className) => $this->container->make($className));
    }
}

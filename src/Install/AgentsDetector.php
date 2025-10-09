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
     * @return array<string>
     */
    public function discoverSystemInstalledAgents(): array
    {
        $platform = Platform::current();

        return $this->getAgents()
            ->filter(fn (Agent $agent): bool => $agent->detectOnSystem($platform))
            ->map(fn (Agent $agent): string => $agent->name())
            ->values()
            ->toArray();
    }

    /**
     * @return array<string>
     */
    public function discoverProjectInstalledAgents(string $basePath): array
    {
        return $this->getAgents()
            ->filter(fn (Agent $agent): bool => $agent->detectInProject($basePath))
            ->map(fn (Agent $agent): string => $agent->name())
            ->values()
            ->toArray();
    }

    /**
     * @return Collection<string, Agent>
     */
    public function getAgents(): Collection
    {
        return collect($this->boostManager->getAgents())
            ->map(fn (string $className) => $this->container->make($className));
    }
}

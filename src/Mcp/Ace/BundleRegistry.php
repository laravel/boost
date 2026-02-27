<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Ace;

use Illuminate\Support\Collection;

class BundleRegistry
{
    /** @var Collection<string, Bundle>|null */
    protected ?Collection $bundles = null;

    /** @var Bundle[] */
    protected array $registered = [];

    /**
     * Register an additional bundle (e.g. from a third-party package).
     */
    public function register(Bundle $bundle): void
    {
        $this->registered[] = $bundle;
        $this->bundles = null;
    }

    /**
     * @return Collection<string, Bundle>
     */
    public function all(): Collection
    {
        return $this->bundles ??= $this->buildBundles();
    }

    public function get(string $id): ?Bundle
    {
        return $this->all()->get($id);
    }

    public function has(string $id): bool
    {
        return $this->all()->has($id);
    }

    /**
     * @return Collection<string, Bundle>
     */
    protected function buildBundles(): Collection
    {
        $bundles = collect([
            new Bundle(
                id: '@database-work',
                description: 'Database development context',
                sliceIds: ['db-schema', 'db-connections', 'app-info', 'foundation'],
                estimatedTokens: 630,
            ),
            new Bundle(
                id: '@testing',
                description: 'Testing context with Pest',
                sliceIds: ['app-info', 'db-schema', 'routes', 'php'],
                estimatedTokens: 800,
            ),
            new Bundle(
                id: '@debug',
                description: 'Debugging context',
                sliceIds: ['last-error', 'browser-logs', 'app-info'],
                sliceParams: ['browser-logs' => ['entries' => 20]],
                estimatedTokens: 450,
            ),
            new Bundle(
                id: '@new-feature',
                description: 'Full context for new feature development',
                sliceIds: ['foundation', 'app-info', 'db-schema', 'routes', 'artisan-commands'],
                estimatedTokens: 1100,
            ),
        ])->keyBy('id');

        foreach ($this->registered as $bundle) {
            $bundles->put($bundle->id, $bundle);
        }

        $excludeList = config('boost.ace.bundles.exclude', []);

        if (! empty($excludeList)) {
            $bundles = $bundles->reject(
                fn (Bundle $bundle) => in_array($bundle->id, $excludeList, true)
            );
        }

        return $bundles;
    }
}

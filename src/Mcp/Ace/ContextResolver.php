<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Ace;

use Illuminate\Support\Collection;

class ContextResolver
{
    public function __construct(
        protected SliceRegistry $sliceRegistry,
        protected BundleRegistry $bundleRegistry,
        protected GuidelineSliceResolver $guidelineResolver,
        protected ToolSliceResolver $toolResolver,
    ) {}

    /**
     * Resolve requested slices and bundles into assembled context.
     *
     * @param  array<string, array<string, mixed>>  $slices  Slice IDs with optional params
     * @param  string[]  $bundles  Bundle IDs to expand
     * @return Collection<string, SliceResult>
     */
    public function resolve(array $slices = [], array $bundles = []): Collection
    {
        $resolvedRequests = $this->expandAndDeduplicate($slices, $bundles);

        return $resolvedRequests->map(
            fn (array $params, string $sliceId) => $this->resolveSlice($sliceId, $params)
        );
    }

    /**
     * Format resolved results into a single text response.
     *
     * @param  Collection<string, SliceResult>  $results
     */
    public function format(Collection $results): string
    {
        $errors = $results->filter(fn (SliceResult $result) => $result->isError);

        $output = $results
            ->reject(fn (SliceResult $result) => $result->isError)
            ->map(fn (SliceResult $result) => "=== {$result->sliceId} ===\n{$result->content}")
            ->join("\n\n");

        if ($errors->isNotEmpty()) {
            $failedIds = $errors->keys()->join(', ');
            $output .= "\n\n[failed: {$failedIds}]";
        }

        return $output;
    }

    /**
     * Expand bundles and merge with explicit slices, deduplicating.
     *
     * @param  array<string, array<string, mixed>>  $slices
     * @param  string[]  $bundles
     * @return Collection<string, array<string, mixed>>  Deduplicated slice ID → params
     */
    protected function expandAndDeduplicate(array $slices, array $bundles): Collection
    {
        $merged = collect($slices);

        foreach ($bundles as $bundleId) {
            $bundle = $this->bundleRegistry->get($bundleId);

            if ($bundle === null) {
                continue;
            }

            foreach ($bundle->sliceIds as $sliceId) {
                if ($merged->has($sliceId)) {
                    continue;
                }

                $merged->put($sliceId, $bundle->sliceParams[$sliceId] ?? []);
            }
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function resolveSlice(string $sliceId, array $params): SliceResult
    {
        $slice = $this->sliceRegistry->get($sliceId);

        if ($slice === null) {
            return new SliceResult($sliceId, "Unknown slice: {$sliceId}", isError: true);
        }

        if ($slice->toolClass !== null) {
            return $this->toolResolver->resolve($slice, $params);
        }

        if ($slice->guidelineKey !== null) {
            return $this->guidelineResolver->resolve($slice);
        }

        return new SliceResult($sliceId, "Slice '{$sliceId}' has no resolver configured.", isError: true);
    }
}

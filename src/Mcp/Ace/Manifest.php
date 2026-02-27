<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Ace;

class Manifest
{
    public function __construct(
        protected SliceRegistry $sliceRegistry,
        protected BundleRegistry $bundleRegistry,
    ) {}

    public function render(): string
    {
        $lines = ["Available context (use resolve-context to load):\n"];

        $slices = $this->sliceRegistry->all()->groupBy('category');

        foreach ($slices as $category => $categorySlices) {
            foreach ($categorySlices as $slice) {
                /** @var ContextSlice $slice */
                $lines[] = $this->formatSliceLine($slice);
            }
        }

        $bundles = $this->bundleRegistry->all();

        if ($bundles->isNotEmpty()) {
            $lines[] = "\nBundles (pre-compiled):";

            foreach ($bundles as $bundle) {
                /** @var Bundle $bundle */
                $lines[] = $this->formatBundleLine($bundle);
            }
        }

        return implode("\n", $lines);
    }

    protected function formatSliceLine(ContextSlice $slice): string
    {
        $tokens = $slice->estimatedTokens > 0 ? "~{$slice->estimatedTokens}t" : 'varies';
        $dynamic = $slice->isDynamic ? ', live' : '';
        $params = $slice->hasParams()
            ? ' (param: '.implode(', ', array_keys($slice->params)).')'
            : '';

        return "[{$slice->category}]  {$slice->id}{$params} ({$tokens}{$dynamic}) - {$slice->label}";
    }

    protected function formatBundleLine(Bundle $bundle): string
    {
        $sliceList = implode(' + ', $bundle->sliceIds);

        return "{$bundle->id} = {$sliceList} (~{$bundle->estimatedTokens}t) - {$bundle->description}";
    }
}

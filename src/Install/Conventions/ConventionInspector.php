<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions;

use Illuminate\Support\Collection;
use Laravel\Boost\Install\Conventions\Contracts\Detector;

class ConventionInspector
{
    public const PRESELECT_THRESHOLD = 0.75;

    /**
     * @param  iterable<Detector>  $detectors
     */
    public function __construct(
        protected SourceRoots $sourceRoots,
        protected FileSampler $sampler,
        protected iterable $detectors,
    ) {}

    /**
     * Run every detector and return the deduped, threshold-marked, confidence-sorted detections.
     *
     * @return Collection<int, Detection>
     */
    public function inspect(?float $threshold = null): Collection
    {
        $threshold ??= self::PRESELECT_THRESHOLD;

        $context = new DetectionContext(
            roots: $this->sourceRoots->resolve(),
            basePath: base_path(),
            sampler: $this->sampler,
        );

        return (new Collection($this->detectors))
            ->flatMap(fn (Detector $detector): Collection => $detector->detect($context))
            ->sortByDesc(fn (Detection $detection): float => $detection->confidence)
            ->unique(fn (Detection $detection): string => $detection->id)
            ->map(fn (Detection $detection): Detection => $detection->withPreselected($detection->isInferred() && $detection->confidence >= $threshold))
            ->values();
    }
}

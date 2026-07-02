<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions;

final class DetectionContext
{
    /**
     * @param  array<int, string>  $roots  Absolute application source roots resolved from PSR-4.
     */
    public function __construct(
        public readonly array $roots,
        public readonly string $basePath,
        public readonly FileSampler $sampler,
    ) {}
}

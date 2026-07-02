<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions\Contracts;

use Illuminate\Support\Collection;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\DetectionContext;

interface Detector
{
    /**
     * A unique, stable identifier for the detector (e.g. "validation-style").
     * Used to dedupe detections and as the checklist option key.
     */
    public function id(): string;

    /**
     * Inspect the sampled codebase and return zero or more inferred conventions.
     *
     * An empty collection means "no dominant convention / not applicable" and
     * the detector contributes no checklist rows.
     *
     * @return Collection<int, Detection>
     */
    public function detect(DetectionContext $context): Collection;
}

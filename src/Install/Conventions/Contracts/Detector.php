<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions\Contracts;

use Illuminate\Support\Collection;
use Laravel\Boost\Install\Conventions\Detection;

interface Detector
{
    /**
     * A unique, stable identifier for the detector (e.g. "package-rules").
     * Used to dedupe detections and as the checklist option key.
     */
    public function id(): string;

    /**
     * Inspect the project and return zero or more rule candidates.
     *
     * An empty collection means "nothing applicable" and the detector
     * contributes no checklist rows.
     *
     * @return Collection<int, Detection>
     */
    public function detect(): Collection;
}

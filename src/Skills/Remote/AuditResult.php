<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

class AuditResult
{
    public function __construct(
        public string $partner,
        public Risk $risk,
        public ?int $alerts = null,
        public ?string $analyzedAt = null,
    ) {
        //
    }
}

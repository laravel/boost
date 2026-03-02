<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

class AuditResult
{
    public function __construct(
        public string $partner,
        public string $risk,
        public ?int $alerts = null,
        public ?string $analyzedAt = null,
    ) {
        //
    }

    public function riskWeight(): int
    {
        return match ($this->risk) {
            'critical' => 5,
            'high' => 4,
            'medium' => 3,
            'low' => 2,
            'safe' => 1,
            default => 0,
        };
    }

    public function riskLabel(): string
    {
        return match ($this->risk) {
            'critical' => 'Critical Risk',
            'high' => 'High Risk',
            'medium' => 'Med Risk',
            'low' => 'Low Risk',
            'safe' => 'Safe',
            default => 'Unknown',
        };
    }

    public function riskColor(): string
    {
        return match ($this->risk) {
            'critical', 'high' => 'red',
            'medium' => 'yellow',
            'low', 'safe' => 'green',
            default => 'gray',
        };
    }
}

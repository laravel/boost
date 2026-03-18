<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

enum Risk: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Safe = 'safe';

    public function weight(): int
    {
        return match ($this) {
            self::Critical => 5,
            self::High => 4,
            self::Medium => 3,
            self::Low => 2,
            self::Safe => 1,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical Risk',
            self::High => 'High Risk',
            self::Medium => 'Med Risk',
            self::Low => 'Low Risk',
            self::Safe => 'Safe',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Critical, self::High => 'red',
            self::Medium => 'yellow',
            self::Low, self::Safe => 'green',
        };
    }
}

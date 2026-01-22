<?php

declare(strict_types=1);

namespace Laravel\Boost\Console\Enums;

enum Theme: string
{
    case LaravelRed = 'laravel_red';
    case Gray = 'gray';
    case Ocean = 'ocean';
    case Vaporwave = 'vaporwave';
    case Sunset = 'sunset';

    /**
     * @return array<int, int>
     */
    public function gradient(): array
    {
        return match ($this) {
            self::LaravelRed => [196, 160, 124, 88, 52, 88],
            self::Gray => [250, 248, 245, 243, 240, 238],
            self::Ocean => [81, 75, 69, 63, 57, 21],
            self::Vaporwave => [213, 177, 141, 105, 69, 39],
            self::Sunset => [214, 208, 202, 196, 160, 124],
        };
    }

    public function primary(): int
    {
        return $this->gradient()[0];
    }

    public function accent(): int
    {
        return $this->gradient()[2];
    }

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }
}

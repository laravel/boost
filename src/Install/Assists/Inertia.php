<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Assists;

use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;

class Inertia
{
    public function __construct(private Roster $roster) {}

    public function gte(string $version): bool
    {
        if ($this->roster->usesVersion(Packages::INERTIA_LARAVEL, $version, '>=')) {
            return true;
        }

        if ($this->roster->usesVersion(Packages::INERTIA_REACT, $version, '>=')) {
            return true;
        }

        if ($this->roster->usesVersion(Packages::INERTIA_SVELTE, $version, '>=')) {
            return true;
        }

        return $this->roster->usesVersion(Packages::INERTIA_VUE, $version, '>=');
    }

    public function hasFormComponent(): bool
    {
        return $this->gte('2.1.0');
    }

    public function hasFormComponentResets(): bool
    {
        return $this->gte('2.1.2');
    }
}

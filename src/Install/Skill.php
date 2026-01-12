<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

class Skill
{
    public function __construct(
        public string $name,
        public string $package,
        public string $path,
        public string $description,
        public bool $custom = false,
    ) {}
}

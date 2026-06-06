<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

final class Command
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly bool $isBlade,
    ) {}
}

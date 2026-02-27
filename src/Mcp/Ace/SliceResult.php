<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Ace;

class SliceResult
{
    public function __construct(
        public readonly string $sliceId,
        public readonly string $content,
        public readonly bool $isError = false,
    ) {}
}

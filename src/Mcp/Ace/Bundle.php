<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Ace;

class Bundle
{
    /**
     * @param  string[]  $sliceIds
     * @param  array<string, array<string, mixed>>  $sliceParams  Per-slice parameter overrides
     */
    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly array $sliceIds,
        public readonly array $sliceParams = [],
        public readonly int $estimatedTokens = 0,
    ) {}
}

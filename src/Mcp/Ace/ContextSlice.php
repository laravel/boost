<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Ace;

class ContextSlice
{
    /**
     * @param  array<string, string>  $params  Parameter definitions (name => description)
     */
    public function __construct(
        public readonly string $id,
        public readonly string $category,
        public readonly string $label,
        public readonly int $estimatedTokens,
        public readonly bool $isDynamic,
        public readonly ?string $guidelineKey = null,
        public readonly ?string $toolClass = null,
        public readonly array $params = [],
    ) {}

    public function hasParams(): bool
    {
        return $this->params !== [];
    }
}

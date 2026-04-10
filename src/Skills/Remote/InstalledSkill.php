<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

class InstalledSkill
{
    public function __construct(
        public string $name,
        public string $source,
        public string $sourceType,
        public string $hash
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(string $name, array $data): self
    {
        return new self(
            name: $name,
            source: $data['source'] ?? '',
            sourceType: $data['sourceType'] ?? 'github',
            hash: $data['computedHash'] ?? '',
        );
    }
}

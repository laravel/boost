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

    public function withCustom(bool $custom): self
    {
        return new self(
            name: $this->name,
            package: $this->package,
            path: $this->path,
            description: $this->description,
            custom: $custom,
        );
    }

    public function displayName(): string
    {
        return $this->custom
            ? '.ai/'.$this->name.'*'
            : $this->name;
    }
}

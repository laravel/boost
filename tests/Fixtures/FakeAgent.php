<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsSkills;

class FakeAgent implements SupportsGuidelines, SupportsSkills
{
    public function __construct(
        private readonly string $path = '',
        private readonly bool $frontmatter = false,
    ) {}

    public function guidelinesPath(): string
    {
        return $this->path;
    }

    public function skillsPath(): string
    {
        return $this->path;
    }

    public function frontmatter(): bool
    {
        return $this->frontmatter;
    }

    public function transformGuidelines(string $markdown): string
    {
        return $markdown;
    }
}

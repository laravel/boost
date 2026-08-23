<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class SkillParseFailures
{
    /** @var array<string, string> */
    protected array $failures = [];

    public function record(string $path, string $message): void
    {
        $this->failures[$path] = $message;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->failures;
    }

    public function isEmpty(): bool
    {
        return $this->failures === [];
    }

    public function flush(): void
    {
        $this->failures = [];
    }
}

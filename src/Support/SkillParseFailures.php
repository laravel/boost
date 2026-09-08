<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class SkillParseFailures
{
    /** @var array<int, string> */
    protected array $failures = [];

    public function record(string $path): void
    {
        $this->failures[$path] = $path;
    }

    public function isEmpty(): bool
    {
        return $this->failures === [];
    }

    /**
     * @return array<int, string>
     */
    public function skillNames(): array
    {
        return collect($this->failures)
            ->map(fn (string $path): string => basename(dirname($path)))
            ->unique()
            ->values()
            ->all();
    }

    public function flush(): void
    {
        $this->failures = [];
    }
}

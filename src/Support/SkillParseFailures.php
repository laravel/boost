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

    /**
     * @return array<int, string>
     */
    public function skillNames(): array
    {
        return collect(array_keys($this->failures))
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

<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class SkillParseFailures
{
    /** @var array<string, string> */
    protected array $failures = [];

    public function record(string $path, string $reason = ''): void
    {
        $this->failures[$path] = $reason;
    }

    public function isEmpty(): bool
    {
        return $this->failures === [];
    }

    /**
     * @return array<int, array{name: string, path: string, reason: string}>
     */
    public function all(): array
    {
        // Frontmatter is unusable, so the directory name is the only identity available.
        return collect($this->failures)
            ->map(fn (string $reason, string $path): array => [
                'name' => basename(dirname($path)),
                'path' => $path,
                'reason' => $reason,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function skillNames(): array
    {
        return collect($this->all())
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    public function flush(): void
    {
        $this->failures = [];
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

use Illuminate\Support\Str;

class RenderFailures
{
    /** @var array<string, ?string> */
    protected array $failures = [];

    public function record(string $path): void
    {
        $this->failures[$path] = $this->packageFromPath($path);
    }

    public function failedFor(string $path): bool
    {
        return array_key_exists($path, $this->failures);
    }

    /**
     * @return array<int, string>
     */
    public function paths(): array
    {
        return array_keys($this->failures);
    }

    /**
     * @return array<int, string>
     */
    public function packages(): array
    {
        return collect($this->failures)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function isEmpty(): bool
    {
        return $this->failures === [];
    }

    public function flush(): void
    {
        $this->failures = [];
    }

    private function packageFromPath(string $path): ?string
    {
        $normalized = str_replace('\\', '/', $path);

        if (! str_contains($normalized, '/vendor/')) {
            return null;
        }

        $segments = explode('/', Str::afterLast($normalized, '/vendor/'));

        return count($segments) >= 3 ? $segments[0].'/'.$segments[1] : null;
    }
}

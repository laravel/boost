<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions;

use Illuminate\Support\Collection;

class SourceRoots
{
    /**
     * Resolve the application's source roots from composer.json PSR-4 autoload,
     * always unioning the default app/ directory. Only existing directories are
     * returned. Falls back to [app_path()] when composer.json is missing/invalid.
     *
     * @return array<int, string>
     */
    public function resolve(): array
    {
        return $this->existingUnique([
            ...$this->psr4Roots(),
            app_path(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function psr4Roots(): array
    {
        $composerJsonPath = base_path('composer.json');

        if (! file_exists($composerJsonPath)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($composerJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            return [];
        }

        $psr4 = $data['autoload']['psr-4'] ?? [];

        return collect(is_array($psr4) ? $psr4 : [])
            ->flatMap(fn ($paths): array => is_array($paths) ? $paths : [$paths])
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->map(fn (string $path): string => base_path($path))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $roots
     * @return array<int, string>
     */
    protected function existingUnique(array $roots): array
    {
        return (new Collection($roots))
            ->map(fn (string $root): string => rtrim($root, DIRECTORY_SEPARATOR))
            ->filter(fn (string $root): bool => is_dir($root))
            ->unique()
            ->values()
            ->all();
    }
}

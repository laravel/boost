<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Conventions;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FileSampler
{
    /** @var array<string, string> */
    protected array $contentCache = [];

    /**
     * Enumerate PHP files per root (optionally within a relative subpath), so a large early root cannot starve later roots.
     *
     * @param  array<int, string>  $roots
     * @return Collection<int, SplFileInfo>
     */
    public function phpFiles(array $roots, ?string $relativeSubpath = null): Collection
    {
        $files = new Collection;

        foreach ($roots as $root) {
            $directory = rtrim($root, DIRECTORY_SEPARATOR);

            if (! is_dir($directory)) {
                continue;
            }

            $finder = Finder::create()->in($directory)->files()->name('*.php')->sortByName();

            if ($relativeSubpath !== null) {
                $subpath = trim(str_replace('\\', '/', $relativeSubpath), '/');

                $finder->path('#(^|/)'.preg_quote($subpath, '#').'/#');
            }

            $files = $files->merge(iterator_to_array($finder, false));
        }

        return $files
            ->unique(fn (SplFileInfo $file): string => $file->getRealPath() ?: $file->getPathname())
            ->values();
    }

    /**
     * Cheap containment check that does not populate the content cache — used to skip files before a
     * heavier parse without retaining every file's bytes for the run.
     */
    public function contains(string $absolutePath, string $needle): bool
    {
        if (array_key_exists($absolutePath, $this->contentCache)) {
            $cached = $this->contentCache[$absolutePath];

            return $cached !== '' && stripos($cached, $needle) !== false;
        }

        $contents = $this->read($absolutePath);

        return $contents !== false && stripos($contents, $needle) !== false;
    }

    public function contents(string $absolutePath): string
    {
        if (array_key_exists($absolutePath, $this->contentCache)) {
            return $this->contentCache[$absolutePath];
        }

        $contents = $this->read($absolutePath);

        return $this->contentCache[$absolutePath] = $contents === false ? '' : $contents;
    }

    protected function read(string $absolutePath): string|false
    {
        return is_file($absolutePath) ? @file_get_contents($absolutePath) : false;
    }
}

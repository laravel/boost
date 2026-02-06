<?php

declare(strict_types=1);

namespace Laravel\Boost\DocsIndex;

use Illuminate\Support\Collection;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class IndexGenerator
{
    /** @var list<string> */
    private const EXCLUDED_FILES = [
        'CLAUDE.md', 'AGENTS.md', 'GEMINI.md',
        'LICENSE.md', 'CODE_OF_CONDUCT.md', 'SECURITY.md',
        'README.md', 'CHANGELOG.md', 'CONTRIBUTING.md',
    ];

    /**
     * Generate a pipe-delimited index of all markdown files in the docs directory.
     *
     * @param  array<string, string|list<string>>  $repoPaths  Map of repo subdir name => sparse path(s)
     */
    public function generate(string $docsDir, array $repoPaths = []): string
    {
        $basePath = base_path($docsDir);

        if (! is_dir($basePath)) {
            return '';
        }

        $excluded = array_map('strtolower', self::EXCLUDED_FILES);

        $finder = Finder::create()->files()->followLinks()->in($basePath)->name(['*.md', '*.mdx']);

        $files = collect(iterator_to_array($finder, false))
            ->reject(fn (SplFileInfo $file): bool => in_array(strtolower($file->getFilename()), $excluded, true))
            ->map(function (SplFileInfo $file) use ($basePath): array {
                $normalizedBase = str_replace('\\', '/', $basePath);
                $normalizedPath = str_replace('\\', '/', $file->getPathname());
                $relativePath = str_replace($normalizedBase.'/', '', $normalizedPath);
                $dir = str_replace('\\', '/', dirname($relativePath));

                return [
                    'dir' => $dir === '.' ? '' : $dir,
                    'file' => basename($relativePath),
                    'path' => $relativePath,
                ];
            })
            ->when(! empty($repoPaths), function (Collection $collection) use ($repoPaths): Collection {
                return $collection->filter(function (array $item) use ($repoPaths): bool {
                    foreach ($repoPaths as $repoDir => $sparsePath) {
                        if (! str_starts_with($item['path'], $repoDir.'/')) {
                            continue;
                        }

                        $paths = is_array($sparsePath) ? $sparsePath : [$sparsePath];

                        if ($paths === ['/']) {
                            return true;
                        }

                        $repoRelative = substr($item['path'], strlen($repoDir) + 1);

                        foreach ($paths as $path) {
                            if (str_starts_with($repoRelative, $path)) {
                                return true;
                            }
                        }

                        return false;
                    }

                    return true;
                });
            })
            ->sortBy(fn (array $item): string => $item['dir'].'/'.$item['file']);

        $grouped = $files->groupBy('dir');

        $parts = [
            '[Laravel Docs Index]',
            "root: {$docsDir}",
            "CRITICAL: Your training data may be OUTDATED for this project's package versions. Always read from {$docsDir}/ before any task. Use Read tool on the file paths below.",
        ];

        foreach ($grouped->sortKeys() as $dir => $dirFiles) {
            $fileNames = $dirFiles->pluck('file')->sort()->values()->implode(',');
            $dirKey = $dir ?: 'root';
            $parts[] = "{$dirKey}:{{{$fileNames}}}";
        }

        return implode('|', $parts);
    }
}

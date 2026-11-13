<?php

declare(strict_types=1);

namespace Laravel\Boost\Rules;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Throwable;

use function Illuminate\Filesystem\join_paths;

class RuleRepository
{
    protected const INDEX_FILENAME = 'index.md';

    protected const MANAGED_DIRNAME = 'boost';

    public function __construct(protected string $directory)
    {
        //
    }

    /**
     * Replace the Boost-managed rule files, keyed by filename slug, and regenerate the index.
     *
     * @param  Collection<string, array{paths: array<int, string>, title: string, content: string}>  $files
     * @return array<int, string> the written file paths
     */
    public function syncManaged(Collection $files): array
    {
        $dir = $this->managedDir();

        $this->removeManagedDir($dir);

        if ($files->isEmpty()) {
            $this->reconcileAfterManagedRemoval();

            return [];
        }

        File::ensureDirectoryExists($dir);

        $written = [];

        foreach ($files as $slug => $file) {
            $path = join_paths($dir, $slug.'.md');
            $written[] = $path;

            File::put($path, $this->renderManagedFile($file['paths'], $file['title'], $file['content']));
        }

        $this->writeIndex();

        return $written;
    }

    /**
     * Remove all Boost-managed rule files. Returns whether the managed directory existed.
     */
    public function clearManaged(): bool
    {
        $dir = $this->managedDir();
        $existed = File::isDirectory($dir);

        $this->removeManagedDir($dir);

        if ($existed) {
            $this->reconcileAfterManagedRemoval();
        }

        return $existed;
    }

    /**
     * Delete the managed directory, throwing if any locked child leaves it partially removed.
     */
    protected function removeManagedDir(string $dir): void
    {
        if (! File::isDirectory($dir)) {
            return;
        }

        File::deleteDirectory($dir);

        if (File::exists($dir)) {
            throw new RuntimeException('Unable to remove managed rules directory: '.$dir);
        }
    }

    protected function reconcileAfterManagedRemoval(): void
    {
        if ($this->parsedFiles()->isNotEmpty()) {
            $this->writeIndex();

            return;
        }

        $indexPath = $this->indexPath();

        if (File::exists($indexPath)) {
            File::delete($indexPath);
        }

        if (File::isDirectory($this->directory) && File::isEmptyDirectory($this->directory)) {
            File::deleteDirectory($this->directory);
        }
    }

    /**
     * Record a rule and return the location it was stored at.
     */
    public function write(string $glob, string $title, string $note): string
    {
        $glob = trim($glob);
        $title = trim(str_replace(["\r\n", "\r", "\n"], ' ', $title));
        $note = trim($note);

        $target = $this->resolveTargetFile($glob);

        if (! File::exists($target['path'])) {
            $this->createFile($target['path'], $target['heading'], [$glob]);
        }

        $this->appendEntry($target['path'], $title, $note);

        $this->writeIndex();

        return $target['path'];
    }

    public function writeIndex(): string
    {
        $rows = $this->parsedFiles()
            ->merge($this->parsedManagedFiles())
            ->filter(fn (array $parsed): bool => $parsed['paths'] !== [])
            ->sortBy(fn (array $parsed): string => $this->relativePath($parsed['file']))
            ->map(fn (array $parsed): string => '| '.implode(', ', $parsed['paths']).' | '.$this->relativePath($parsed['file']).' |')
            ->values()
            ->join("\n");

        $table = $rows === ''
            ? 'No rules recorded yet.'
            : "| Applies to | Rule file |\n| --- | --- |\n".$rows;

        $body = "# Project Rules Index\n\n"
            ."Before planning or editing, check every row for a glob matching the file's path and read every matching rule file. A file may match multiple rows.\n\n"
            .$table."\n";

        $path = $this->indexPath();

        File::ensureDirectoryExists($this->directory);
        File::put($path, $body);

        return $path;
    }

    public function exists(): bool
    {
        return File::isDirectory($this->directory);
    }

    /**
     * Rule files that still contain unresolved merge conflict markers.
     *
     * @return array<int, string>
     */
    public function conflictedFiles(): array
    {
        return collect([...$this->files(), ...$this->managedFiles()])
            ->filter(fn (string $file): bool => preg_match('/^(<{7}|>{7})(\s|$)/m', (string) File::get($file)) === 1)
            ->values()
            ->all();
    }

    /**
     * Rule files the index skips because their frontmatter declares no paths.
     *
     * @return array<int, string>
     */
    public function unindexedFiles(): array
    {
        return $this->parsedFiles()
            ->merge($this->parsedManagedFiles())
            ->filter(fn (array $parsed): bool => $parsed['paths'] === [])
            ->map(fn (array $parsed): string => $parsed['file'])
            ->values()
            ->all();
    }

    public function normalizeGlob(string $glob): string
    {
        return $this->relativePath(trim($glob));
    }

    public function relativePath(string $path): string
    {
        $path = Str::replace(DIRECTORY_SEPARATOR, '/', $path);
        $base = Str::finish(Str::replace(DIRECTORY_SEPARATOR, '/', base_path()), '/');

        return ltrim(str_starts_with($path, $base) ? substr($path, strlen($base)) : $path, '/');
    }

    /**
     * Only a file with the requested glob as its sole path can accept another rule.
     *
     * @return array{path: string, heading: string}
     */
    protected function resolveTargetFile(string $glob): array
    {
        $allParsed = $this->parsedFiles();

        $existing = $allParsed->first(fn (array $parsed): bool => $parsed['paths'] === [$glob]);

        if ($existing !== null) {
            return ['path' => $existing['file'], 'heading' => ''];
        }

        $path = $this->uniqueFilePath($glob, $allParsed);

        return [
            'path' => $path,
            'heading' => Str::headline(basename($path, '.md')),
        ];
    }

    /**
     * @param  Collection<int, array{file: string, paths: array<int, string>, body: string}>  $allParsed
     */
    protected function uniqueFilePath(string $glob, Collection $allParsed): string
    {
        $segments = $this->meaningfulSegments($glob);
        $taken = $allParsed->map(fn (array $parsed): string => $parsed['file'])->all();
        $reserved = $this->indexPath();

        $slugs = [
            $this->slugForSegments(array_slice($segments, -1)),
            $this->slugForSegments(array_slice($this->segments($glob), -2)),
        ];
        $counter = count($segments);

        for ($take = 2; $take <= $counter; $take++) {
            $slugs[] = $this->slugForSegments(array_slice($segments, -$take));
        }

        $candidates = array_values(array_unique(array_filter($slugs, filled(...))));

        if ($candidates === []) {
            $candidates[] = 'general';
        }

        foreach ($candidates as $candidate) {
            $path = join_paths($this->directory, $candidate.'.md');

            if ($path !== $reserved && ! in_array($path, $taken, true) && ! File::exists($path)) {
                return $path;
            }
        }

        $base = end($candidates);
        $suffix = 2;

        do {
            $path = join_paths($this->directory, $base.'-'.$suffix.'.md');
            $suffix++;
        } while (in_array($path, $taken, true) || File::exists($path));

        return $path;
    }

    /**
     * @return array<int, string>
     */
    protected function segments(string $glob): array
    {
        return Str::of($glob)
            ->explode('/')
            ->filter(static fn (string $segment): bool => filled($segment))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function meaningfulSegments(string $glob): array
    {
        $segments = collect($this->segments($glob));

        $directories = $segments->reject(static fn (string $segment): bool => Str::contains($segment, ['*', '.']))->values();

        return ($directories->isNotEmpty() ? $directories : $segments)->all();
    }

    /**
     * @param  array<int, string>  $segments
     */
    protected function slugForSegments(array $segments): string
    {
        $words = array_map(
            static fn (string $segment): string => Str::contains($segment, ['*', '.']) ? Str::lower(str_replace('.', ' ', $segment)) : Str::snake($segment),
            $segments,
        );

        return Str::slug(implode(' ', $words));
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function createFile(string $path, string $heading, array $paths): void
    {
        File::ensureDirectoryExists(dirname($path));

        File::put($path, $this->renderFrontmatter($paths).'# '.$heading."\n");
    }

    protected function appendEntry(string $path, string $title, string $note): void
    {
        $contents = rtrim((string) File::get($path), "\n");

        File::put($path, $contents."\n\n## ".$title."\n".$note."\n");
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function renderFrontmatter(array $paths): string
    {
        $yaml = Yaml::dump(['paths' => array_values($paths)], 2, 2);

        return "---\n".$yaml."---\n\n";
    }

    /**
     * @return array{paths: array<int, string>, body: string}
     */
    protected function parse(string $path): array
    {
        return RuleFrontmatter::parse((string) File::get($path));
    }

    /**
     * @return array<int, string>
     */
    protected function files(): array
    {
        return $this->projectMarkdownFilesIn($this->directory);
    }

    /**
     * @return Collection<int, array{file: string, paths: array<int, string>, body: string}>
     */
    protected function parsedFiles(): Collection
    {
        return $this->parseAll($this->files());
    }

    protected function managedDir(): string
    {
        return join_paths($this->directory, self::MANAGED_DIRNAME);
    }

    protected function indexPath(): string
    {
        return join_paths($this->directory, self::INDEX_FILENAME);
    }

    /**
     * @return array<int, string>
     */
    protected function managedFiles(): array
    {
        return $this->markdownFilesIn($this->managedDir());
    }

    /**
     * @return Collection<int, array{file: string, paths: array<int, string>, body: string}>
     */
    protected function parsedManagedFiles(): Collection
    {
        return $this->parseAll($this->managedFiles());
    }

    /**
     * @return array<int, string>
     */
    protected function markdownFilesIn(string $dir): array
    {
        if (! File::isDirectory($dir)) {
            return [];
        }

        return File::glob(join_paths($dir, '*.md')) ?: [];
    }

    /**
     * Project rule files at any depth, so a team can group related rules in
     * sub-folders (e.g. `.ai/rules/billing/stripe.md`). The index and the
     * Boost-managed folder are not project rules.
     *
     * @return array<int, string>
     */
    protected function projectMarkdownFilesIn(string $dir): array
    {
        if (! File::isDirectory($dir)) {
            return [];
        }

        $managedDir = Str::finish($this->managedDir(), DIRECTORY_SEPARATOR);
        $indexPath = $this->indexPath();

        return collect(File::allFiles($dir))
            ->map(fn (SplFileInfo $file): string => $file->getPathname())
            ->filter(fn (string $file): bool => str_ends_with($file, '.md'))
            ->reject(fn (string $file): bool => $file === $indexPath || str_starts_with($file, $managedDir))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $files
     * @return Collection<int, array{file: string, paths: array<int, string>, body: string}>
     */
    protected function parseAll(array $files): Collection
    {
        return collect($files)
            ->map(function (string $file): ?array {
                try {
                    return ['file' => $file, ...$this->parse($file)];
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function renderManagedFile(array $paths, string $title, string $content): string
    {
        $content = trim($content);

        $heading = preg_match('/^#+\s/', $content) === 1 ? '' : '# '.$title."\n\n";

        return $this->renderFrontmatter($paths).$heading.$content."\n";
    }
}

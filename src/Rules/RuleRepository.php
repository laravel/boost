<?php

declare(strict_types=1);

namespace Laravel\Boost\Rules;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class RuleRepository
{
    public function __construct(protected string $directory)
    {
        //
    }

    /**
     * Record a rule and return the location it was stored at.
     */
    public function write(string $glob, string $title, string $note): string
    {
        $glob = trim($glob);
        $title = trim((string) preg_replace('/\R/', ' ', $title));
        $note = trim($note);

        $target = $this->resolveTargetFile($glob);

        if (! File::exists($target['path'])) {
            $this->createFile($target['path'], $target['heading'], [$glob]);
        } else {
            $this->ensureGlobApplied($target['path'], $glob, $target['parsed']);
        }

        $this->appendEntry($target['path'], $title, $note);

        $this->writeIndex();

        return $target['path'];
    }

    public function writeIndex(): string
    {
        $rows = $this->parsedFiles()
            ->filter(fn (array $parsed): bool => $parsed['paths'] !== [])
            ->map(fn (array $parsed): string => '| '.implode(', ', $parsed['paths']).' | '.$this->relativePath($parsed['file']).' |')
            ->join("\n");

        $table = $rows === ''
            ? 'No rules recorded yet.'
            : "| Applies to | Rule file |\n| --- | --- |\n".$rows;

        $body = "# Project Rules Index\n\n"
            ."Before planning or editing, find the row whose globs match the file's path and read that rule file.\n\n"
            .$table."\n";

        $path = $this->directory.DIRECTORY_SEPARATOR.'index.md';

        File::ensureDirectoryExists($this->directory);
        File::put($path, $body);

        return $path;
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
     * @return array{path: string, heading: string, parsed: array<string, mixed>|null}
     */
    protected function resolveTargetFile(string $glob): array
    {
        $allParsed = $this->parsedFiles();

        $existing = $allParsed->first(fn (array $parsed): bool => in_array($glob, $parsed['paths'], true));

        if ($existing !== null) {
            return ['path' => $existing['file'], 'heading' => '', 'parsed' => $existing];
        }

        $name = $this->fileNameForGlob($glob);
        $path = $this->directory.DIRECTORY_SEPARATOR.$name.'.md';

        return [
            'path' => $path,
            'heading' => Str::headline($name),
            'parsed' => $allParsed->first(fn (array $parsed): bool => $parsed['file'] === $path),
        ];
    }

    protected function fileNameForGlob(string $glob): string
    {
        $last = Str::of($glob)->trim('/')->explode('/')
            ->filter(static fn (string $segment): bool => filled($segment) && ! str_contains($segment, '*') && ! str_contains($segment, '.'))
            ->last();

        $slug = Str::slug(Str::snake((string) $last));

        return blank($slug) ? 'general' : $slug;
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function createFile(string $path, string $heading, array $paths): void
    {
        File::ensureDirectoryExists(dirname($path));

        File::put($path, $this->renderFrontmatter($paths).'# '.$heading."\n");
    }

    protected function ensureGlobApplied(string $path, string $glob, ?array $parsed = null): void
    {
        if ($parsed === null) {
            try {
                $parsed = $this->parse($path);
            } catch (Throwable) {
                $raw = (string) preg_replace('/\R/', "\n", (string) File::get($path));
                $parsed = ['paths' => [], 'body' => $raw];
            }
        }

        if (in_array($glob, $parsed['paths'], true)) {
            return;
        }

        $paths = [...$parsed['paths'], $glob];
        $frontmatter = $this->renderFrontmatter($paths);

        $body = (string) preg_replace('/^---\r?\n.*?\r?\n---\r?\n?/s', '', (string) $parsed['body']);

        File::put($path, $frontmatter.ltrim($body, "\n"));
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
        $raw = (string) preg_replace('/\R/', "\n", (string) File::get($path));

        if (preg_match('/^---\n(.*?)\n---\n?(.*)$/s', $raw, $matches) !== 1) {
            return ['paths' => [], 'body' => $raw];
        }

        $front = Yaml::parse($matches[1]) ?: [];

        return [
            'paths' => array_values(array_filter((array) ($front['paths'] ?? []), is_string(...))),
            'body' => $matches[2],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function files(): array
    {
        if (! File::isDirectory($this->directory)) {
            return [];
        }

        return collect(File::glob($this->directory.DIRECTORY_SEPARATOR.'*.md') ?: [])
            ->reject(fn (string $file): bool => basename($file) === 'index.md')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{file: string, paths: array<int, string>, body: string}>
     */
    protected function parsedFiles(): Collection
    {
        return collect($this->files())
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
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Memory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class MemoryRepository
{
    /**
     * @var array<int, string>
     */
    public const TYPES = ['decision', 'gotcha', 'rule'];

    private const TYPE_PATTERN = 'decision|gotcha|rule';

    public function __construct(protected string $directory) {}

    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * @return array{file: string, created: bool, type: string, title: string}
     */
    public function write(string $glob, string $type, string $title, string $note): array
    {
        $glob = trim($glob);
        $type = strtolower(trim($type));
        $title = trim((string) preg_replace('/\R/', ' ', $title));
        $note = trim($note);

        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Memory type must be one of: '.implode(', ', self::TYPES).'.');
        }

        $target = $this->resolveTargetFile($glob);
        $created = ! is_file($target['path']);

        if ($created) {
            $this->createFile($target['path'], $target['heading'], [$glob]);
        } else {
            $this->ensureGlobApplied($target['path'], $glob, $target['parsed']);
        }

        $this->appendEntry($target['path'], $type, $title, $note);

        return [
            'file' => $target['path'],
            'created' => $created,
            'type' => $type,
            'title' => $title,
        ];
    }

    /**
     * @return array<int, array{path: string, applies_to: array<int, string>}>
     */
    public function filesForPath(string $path): array
    {
        return $this->parsedFiles()
            ->filter(fn (array $parsed): bool => $this->globsMatchPath($parsed['applies_to'], trim($path)))
            ->map(fn (array $parsed): array => [
                'path' => $parsed['file'],
                'applies_to' => $parsed['applies_to'],
            ])
            ->values()
            ->all();
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

        $existing = $allParsed->first(fn (array $parsed): bool => in_array($glob, $parsed['applies_to'], true));

        if ($existing !== null) {
            return ['path' => $existing['file'], 'heading' => $existing['heading'], 'parsed' => $existing];
        }

        $name = $this->fileNameForGlob($glob);
        $path = $this->directory.DIRECTORY_SEPARATOR.$name.'.md';

        $existingByName = $allParsed->first(fn (array $parsed): bool => $parsed['file'] === $path);

        return [
            'path' => $path,
            'heading' => Str::headline($name),
            'parsed' => $existingByName,
        ];
    }

    protected function fileNameForGlob(string $glob): string
    {
        $last = Str::of($glob)->trim('/')->explode('/')
            ->filter(static fn (string $segment): bool => $segment !== '' && ! str_contains($segment, '*') && ! str_contains($segment, '.'))
            ->last();

        if ($last === null) {
            return 'general';
        }

        $slug = Str::slug(Str::snake($last));

        return $slug === '' ? 'general' : $slug;
    }

    /**
     * @param  array<int, string>  $appliesTo
     */
    protected function createFile(string $path, string $heading, array $appliesTo): void
    {
        File::ensureDirectoryExists(dirname($path));

        file_put_contents($path, $this->renderFrontmatter($appliesTo).'# '.$heading."\n");
    }

    protected function ensureGlobApplied(string $path, string $glob, ?array $parsed = null): void
    {
        if ($parsed === null) {
            try {
                $parsed = $this->parse($path);
            } catch (Throwable) {
                $raw = (string) preg_replace('/\R/', "\n", (string) file_get_contents($path));
                $parsed = ['applies_to' => [], 'body' => $raw, 'heading' => '', 'entries' => []];
            }
        }

        if (in_array($glob, $parsed['applies_to'], true)) {
            return;
        }

        $appliesTo = [...$parsed['applies_to'], $glob];
        $frontmatter = $this->renderFrontmatter($appliesTo);

        // fix any invalid frontmatter
        $body = (string) preg_replace('/^---\r?\n.*?\r?\n---\r?\n?/s', '', (string) $parsed['body']);

        file_put_contents($path, $frontmatter.ltrim($body, "\n"));
    }

    protected function appendEntry(string $path, string $type, string $title, string $note): void
    {
        $contents = rtrim((string) file_get_contents($path), "\n");

        file_put_contents($path, $contents."\n\n## [".$type.'] '.$title."\n".$note."\n");
    }

    /**
     * @param  array<int, string>  $appliesTo
     */
    protected function renderFrontmatter(array $appliesTo): string
    {
        $yaml = Yaml::dump(['applies_to' => array_values($appliesTo)], 2, 2);

        return "---\n".$yaml."---\n\n";
    }

    /**
     * @return array{applies_to: array<int, string>, heading: string, body: string, entries: array<int, array{type: string, title: string, body: string}>}
     */
    protected function parse(string $path): array
    {
        $raw = (string) preg_replace('/\R/', "\n", (string) file_get_contents($path));

        $appliesTo = [];
        $body = $raw;

        if (preg_match('/^---\n(.*?)\n---\n?(.*)$/s', $raw, $matches) === 1) {
            $front = Yaml::parse($matches[1]) ?: [];
            $appliesTo = array_values(array_filter((array) ($front['applies_to'] ?? []), is_string(...)));
            $body = $matches[2];
        }

        $heading = Str::of($body)->after('# ')->before("\n")->trim()->value();

        $entries = [];

        if (preg_match_all('/(?<=\n\n)## \[('.self::TYPE_PATTERN.')\]\s*(.+?)\n(.*?)(?=\n\n## |\z)/s', $body, $entryMatches, PREG_SET_ORDER) > 0) {
            $entries = collect($entryMatches)
                ->map(static fn (array $match): array => [
                    'type' => strtolower(trim($match[1])),
                    'title' => trim($match[2]),
                    'body' => trim($match[3]),
                ])
                ->all();
        }

        return [
            'applies_to' => $appliesTo,
            'heading' => $heading,
            'body' => $body,
            'entries' => $entries,
        ];
    }

    /**
     * @param  array<int, string>  $globs
     */
    protected function globsMatchPath(array $globs, string $path): bool
    {
        if ($globs === []) {
            return true;
        }

        $path = ltrim(Str::replace(DIRECTORY_SEPARATOR, '/', $path), '/');

        foreach ($globs as $glob) {
            $pattern = '#^'.str_replace(['\*\*', '\*', '\?'], ['.*', '[^/]*', '[^/]'], preg_quote(trim($glob, '/'), '#')).'$#u';

            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function files(): array
    {
        if (! is_dir($this->directory)) {
            return [];
        }

        return collect(glob($this->directory.DIRECTORY_SEPARATOR.'*.md') ?: [])
            ->reject(fn (string $file): bool => basename($file) === 'index.md')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{file: string, applies_to: array<int, string>, heading: string, body: string, entries: array<int, array{type: string, title: string, body: string}>}>
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

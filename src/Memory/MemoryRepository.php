<?php

declare(strict_types=1);

namespace Laravel\Boost\Memory;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class MemoryRepository
{
    /**
     * The memory entry types Boost ships with.
     *
     * @var array<int, string>
     */
    public const TYPES = ['decision', 'gotcha', 'rule'];

    public function __construct(protected string $directory) {}

    /**
     * The absolute path to the memory directory.
     */
    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * The absolute path to the generated index file.
     */
    public function indexPath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'index.md';
    }

    /**
     * Record a memory for a glob of files, routing it into a shared area file.
     *
     * @return array{file: string, created: bool, type: string, title: string}
     */
    public function write(string $glob, string $type, string $title, string $note): array
    {
        $glob = trim($glob);
        $type = strtolower(trim($type));
        $title = trim(str_replace(["\r\n", "\r", "\n"], ' ', $title));
        $note = trim($note);

        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Memory type must be one of: '.implode(', ', self::TYPES).'.');
        }

        $target = $this->resolveTargetFile($glob);
        $created = ! is_file($target['path']);

        if ($created) {
            $this->createFile($target['path'], $target['heading'], [$glob]);
        } else {
            $this->ensureGlobApplied($target['path'], $glob);
        }

        $this->appendEntry($target['path'], $type, $title, $note);
        $this->rebuildIndex();

        return [
            'file' => $target['path'],
            'created' => $created,
            'type' => $type,
            'title' => $title,
        ];
    }

    /**
     * Find memory entries by the file path being worked on, a keyword, or both.
     *
     * @return array<int, array{file: string, applies_to: array<int, string>, type: string, title: string, body: string}>
     */
    public function search(?string $path = null, ?string $query = null): array
    {
        $path = $path !== null ? trim($path) : null;
        $query = $query !== null ? strtolower(trim($query)) : null;

        $matches = [];

        foreach ($this->files() as $file) {
            try {
                $parsed = $this->parse($file);
            } catch (Throwable) {
                continue;
            }

            $fileName = basename($file);

            $pathMatchesFile = $path !== null && $path !== ''
                ? $this->globsMatchPath($parsed['applies_to'], $path)
                : null;

            foreach ($parsed['entries'] as $entry) {
                if ($pathMatchesFile === false) {
                    continue;
                }

                if ($query !== null && $query !== '' && ! $this->entryMatchesQuery($entry, $query)) {
                    continue;
                }

                $matches[] = [
                    'file' => $fileName,
                    'applies_to' => $parsed['applies_to'],
                    'type' => $entry['type'],
                    'title' => $entry['title'],
                    'body' => $entry['body'],
                ];
            }
        }

        return $matches;
    }

    /**
     * Regenerate the glob to file index so non-MCP agents can route by hand.
     */
    public function rebuildIndex(): void
    {
        $files = $this->files();

        $rows = [];

        foreach ($files as $file) {
            try {
                $parsed = $this->parse($file);
            } catch (Throwable) {
                continue;
            }

            $name = basename($file);

            foreach ($parsed['applies_to'] as $glob) {
                $rows[] = '| `'.$glob.'` | ['.$name.']('.$name.') |';
            }
        }

        sort($rows);

        $body = "# Project Memory Index\n\n"
            ."This file maps file globs to the memory that documents them. It is generated\n"
            ."by Laravel Boost; edit the linked files, not this index.\n\n";

        if ($rows === []) {
            $body .= "No memories recorded yet.\n";
        } else {
            $body .= "| Applies to | Memory |\n| --- | --- |\n".implode("\n", $rows)."\n";
        }

        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }

        file_put_contents($this->indexPath(), $body);
    }

    /**
     * Resolve the file a glob should be written to, plus its heading.
     *
     * @return array{path: string, heading: string}
     */
    protected function resolveTargetFile(string $glob): array
    {
        foreach ($this->files() as $file) {
            try {
                $parsed = $this->parse($file);
            } catch (Throwable) {
                continue;
            }

            if (in_array($glob, $parsed['applies_to'], true)) {
                return ['path' => $file, 'heading' => $parsed['heading']];
            }
        }

        $name = $this->fileNameForGlob($glob);

        return [
            'path' => $this->directory.DIRECTORY_SEPARATOR.$name.'.md',
            'heading' => Str::headline($name),
        ];
    }

    /**
     * Derive a stable, shared file name from a glob (app/Http/Controllers/** => controllers).
     */
    protected function fileNameForGlob(string $glob): string
    {
        $segments = array_filter(
            explode('/', trim($glob, '/')),
            static fn (string $segment): bool => $segment !== '' && ! str_contains($segment, '*') && ! str_contains($segment, '.'),
        );

        $last = end($segments);

        if ($last === false) {
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
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $frontmatter = $this->renderFrontmatter($appliesTo);

        file_put_contents($path, $frontmatter.'# '.$heading."\n");
    }

    protected function ensureGlobApplied(string $path, string $glob): void
    {
        try {
            $parsed = $this->parse($path);
        } catch (Throwable) {
            // Parse failed (bad YAML); preserve raw file bytes so existing entries survive.
            // The strip regex below will remove the broken frontmatter block.
            $raw = str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($path));
            $parsed = ['applies_to' => [], 'body' => $raw, 'heading' => '', 'entries' => []];
        }

        if (in_array($glob, $parsed['applies_to'], true)) {
            return;
        }

        $appliesTo = [...$parsed['applies_to'], $glob];
        $frontmatter = $this->renderFrontmatter($appliesTo);

        // Strip any existing (possibly malformed) frontmatter from the body before prepending
        // the new valid frontmatter, so a broken file is repaired rather than doubled.
        $body = (string) preg_replace('/^---\r?\n.*?\r?\n---\r?\n?/s', '', $parsed['body']);

        file_put_contents($path, $frontmatter.ltrim($body, "\n"));
    }

    protected function appendEntry(string $path, string $type, string $title, string $note): void
    {
        $contents = rtrim((string) file_get_contents($path), "\n");
        $entry = "\n\n## [".$type.'] '.$title."\n".$note."\n";

        file_put_contents($path, $contents.$entry);
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
     * Parse a memory file into its globs, heading, body, and entries.
     *
     * @return array{applies_to: array<int, string>, heading: string, body: string, entries: array<int, array{type: string, title: string, body: string}>}
     */
    protected function parse(string $path): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($path));

        $appliesTo = [];
        $body = $raw;

        if (preg_match('/^---\n(.*?)\n---\n?(.*)$/s', $raw, $matches) === 1) {
            $front = Yaml::parse($matches[1]) ?: [];
            $appliesTo = array_values(array_filter((array) ($front['applies_to'] ?? []), is_string(...)));
            $body = $matches[2];
        }

        $heading = Str::of($body)->after('# ')->before("\n")->trim()->value();

        $entries = [];

        // Require a blank line before ## to avoid matching `## [` inside note bodies.
        if (preg_match_all('/^## \[(\w+)\]\s*(.+?)\n(.*?)(?=\n\n## |\z)/ms', $body, $entryMatches, PREG_SET_ORDER) > 0) {
            foreach ($entryMatches as $match) {
                $entries[] = [
                    'type' => strtolower(trim($match[1])),
                    'title' => trim($match[2]),
                    'body' => trim($match[3]),
                ];
            }
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
        $path = ltrim(str_replace('\\', '/', $path), '/');

        foreach ($globs as $glob) {
            if (fnmatch(trim($glob, '/'), $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{type: string, title: string, body: string}  $entry
     */
    protected function entryMatchesQuery(array $entry, string $query): bool
    {
        $haystack = strtolower($entry['title'].' '.$entry['body'].' '.$entry['type']);

        foreach (preg_split('/\s+/', $query) ?: [] as $term) {
            if ($term !== '' && ! str_contains($haystack, $term)) {
                return false;
            }
        }

        return true;
    }

    /**
     * All memory files, excluding the generated index.
     *
     * @return array<int, string>
     */
    protected function files(): array
    {
        if (! is_dir($this->directory)) {
            return [];
        }

        $files = glob($this->directory.DIRECTORY_SEPARATOR.'*.md') ?: [];

        return array_values(array_filter(
            $files,
            fn (string $file): bool => basename($file) !== 'index.md',
        ));
    }
}

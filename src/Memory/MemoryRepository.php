<?php

declare(strict_types=1);

namespace Laravel\Boost\Memory;

use Illuminate\Support\Collection;
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

    private const TYPE_PATTERN = 'decision|gotcha|rule';

    public function __construct(protected string $directory) {}

    /**
     * The absolute path to the memory directory.
     */
    public function directory(): string
    {
        return $this->directory;
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
     * Find memory files whose globs cover the given path.
     *
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

    /**
     * Resolve the file a glob should be written to, plus its heading and already-parsed data.
     * Passing parsed data through avoids re-reading the file in ensureGlobApplied().
     *
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

    /**
     * Derive a stable, shared file name from a glob (app/Http/Controllers/** => controllers).
     */
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
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $frontmatter = $this->renderFrontmatter($appliesTo);

        file_put_contents($path, $frontmatter.'# '.$heading."\n");
    }

    protected function ensureGlobApplied(string $path, string $glob, ?array $parsed = null): void
    {
        if ($parsed === null) {
            try {
                $parsed = $this->parse($path);
            } catch (Throwable) {
                // Parse failed (bad YAML); preserve raw file bytes so existing entries survive.
                // The strip regex below will remove the broken frontmatter block.
                $raw = (string) preg_replace('/\R/', "\n", (string) file_get_contents($path));
                $parsed = ['applies_to' => [], 'body' => $raw, 'heading' => '', 'entries' => []];
            }
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

        // Require a blank line before ## and restrict to known types so ## [ lines in note bodies are not parsed as entries.
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
     * Strip the project base path prefix so the returned path is repo-relative.
     */
    public function relativePath(string $path): string
    {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $base = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', base_path()), '/').'/';

        if (str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        return ltrim($path, '/');
    }

    /**
     * @param  array<int, string>  $globs
     */
    protected function globsMatchPath(array $globs, string $path): bool
    {
        if ($globs === []) {
            return true;
        }

        $path = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $path), '/');

        foreach ($globs as $glob) {
            if (Str::is(trim($glob, '/'), $path)) {
                return true;
            }
        }

        return false;
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

        return collect($files)
            ->reject(fn (string $file): bool => basename($file) === 'index.md')
            ->values()
            ->all();
    }

    /**
     * Parse every memory file, skipping any that fail to parse.
     *
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

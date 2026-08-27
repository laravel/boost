<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

use Illuminate\Support\Str;
use InvalidArgumentException;

class GitHubRepository
{
    public function __construct(public string $owner, public string $repo, public string $path = '', public string $branch = '')
    {
        $path = trim($path, '/');

        // A path copied from a file view points at SKILL.md, not the directory holding it.
        $directory = basename($path) === 'SKILL.md' ? dirname($path) : $path;

        $this->path = $directory === '.' ? '' : $directory;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromInput(string $input): self
    {
        [$input, $branch] = self::normalizeUrl($input);

        return self::parseOwnerRepoPath($input, $branch);
    }

    public function fullName(): string
    {
        return $this->owner.'/'.$this->repo;
    }

    public function source(): string
    {
        return $this->path === ''
            ? $this->fullName()
            : $this->fullName().'/'.$this->path;
    }

    /**
     * @return array{0: string, 1: string}
     *
     * @throws InvalidArgumentException
     */
    private static function normalizeUrl(string $input): array
    {
        $isUrl = Str::startsWith($input, ['http://', 'https://']);

        if (! $isUrl) {
            return [$input, ''];
        }

        $parsed = parse_url($input);

        $host = $parsed['host'] ?? '';
        $isGitHubUrl = $host === 'github.com' || Str::endsWith($host, '.github.com');

        if (! $isGitHubUrl) {
            throw new InvalidArgumentException('Only GitHub URLs are supported.');
        }

        $path = Str::of($parsed['path'] ?? '')->trim('/')->toString();

        // ponytail: a branch name containing a slash is indistinguishable from the path after it.
        if (preg_match('#^(?P<repository>[^/]+/[^/]+)/(?:tree|blob)/(?P<branch>[^/]+)/?(?P<path>.*)$#', $path, $matches) === 1) {
            return [rtrim($matches['repository'].'/'.$matches['path'], '/'), $matches['branch']];
        }

        return [$path, ''];
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function parseOwnerRepoPath(string $input, string $branch = ''): self
    {
        $parts = explode('/', $input);

        if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException('Invalid repository format. Expected: owner/repo, owner/repo/path, or GitHub URL');
        }

        return new self(
            owner: $parts[0],
            repo: $parts[1],
            path: implode('/', array_slice($parts, 2)),
            branch: $branch,
        );
    }
}

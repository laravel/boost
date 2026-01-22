<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

use Illuminate\Support\Str;
use InvalidArgumentException;

class GitHubRepository
{
    public function __construct(public string $owner, public string $repo, public string $path = '')
    {
        //
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromInput(string $input): self
    {
        $input = self::normalizeUrl($input);

        return self::parseOwnerRepoPath($input);
    }

    public function fullName(): string
    {
        return $this->owner.'/'.$this->repo;
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function normalizeUrl(string $input): string
    {
        $isUrl = Str::startsWith($input, ['http://', 'https://']);

        if (! $isUrl) {
            return $input;
        }

        $parsed = parse_url($input);

        $host = $parsed['host'] ?? '';
        $isGitHubUrl = $host === 'github.com' || Str::endsWith($host, '.github.com');

        if (! $isGitHubUrl) {
            throw new InvalidArgumentException('Only GitHub URLs are supported.');
        }

        $path = Str::of($parsed['path'] ?? '')->trim('/')->toString();

        if (Str::contains($path, '/tree/')) {
            return Str::of($path)->replaceMatches('#/tree/[^/]+#', '')->toString();
        }

        return $path;
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function parseOwnerRepoPath(string $input): self
    {
        $parts = explode('/', $input);

        if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException('Invalid repository format. Expected: owner/repo, owner/repo/path, or GitHub URL');
        }

        return new self(
            owner: $parts[0],
            repo: $parts[1],
            path: implode('/', array_slice($parts, 2)),
        );
    }
}

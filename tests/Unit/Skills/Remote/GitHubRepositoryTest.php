<?php

declare(strict_types=1);

use Laravel\Boost\Skills\Remote\GitHubRepository;

it('parses valid repository input', function (string $input, string $owner, string $repo, string $path): void {
    $result = GitHubRepository::fromInput($input);

    expect($result->owner)->toBe($owner)
        ->and($result->repo)->toBe($repo)
        ->and($result->path)->toBe($path);
})->with([
    'owner/repo format' => ['owner/repo', 'owner', 'repo', ''],
    'owner/repo/path format' => ['owner/repo/path/to/skills', 'owner', 'repo', 'path/to/skills'],
    'full GitHub URL' => ['https://github.com/owner/repo', 'owner', 'repo', ''],
    'GitHub URL with trailing slash' => ['https://github.com/owner/repo/', 'owner', 'repo', ''],
    'HTTP GitHub URL' => ['http://github.com/owner/repo', 'owner', 'repo', ''],
    'GitHub URL with tree/branch' => ['https://github.com/owner/repo/tree/main/skills', 'owner', 'repo', 'skills'],
    'GitHub URL with tree/branch and nested path' => ['https://github.com/owner/repo/tree/feature-branch/path/to/skills', 'owner', 'repo', 'path/to/skills'],
    'complex branch names in tree URLs' => ['https://github.com/owner/repo/tree/feature/my-branch/skills', 'owner', 'repo', 'my-branch/skills'],
]);

it('throws for invalid input', function (string $input, string $message): void {
    expect(fn (): \Laravel\Boost\Skills\Remote\GitHubRepository => GitHubRepository::fromInput($input))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'no slash' => ['invalid-format', 'Invalid repository format'],
    'empty owner' => ['/repo', 'Invalid repository format'],
    'empty repo' => ['owner/', 'Invalid repository format'],
    'GitLab URL' => ['https://gitlab.com/owner/repo', 'Only GitHub URLs are supported'],
    'Bitbucket URL' => ['https://bitbucket.org/owner/repo', 'Only GitHub URLs are supported'],
]);

it('returns full name from fullName method', function (): void {
    $repo = new GitHubRepository('owner', 'repo', 'path');

    expect($repo->fullName())->toBe('owner/repo');
});

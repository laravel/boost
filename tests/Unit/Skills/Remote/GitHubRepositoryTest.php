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
    'GitHub URL pointing at a skill directory' => ['https://github.com/owner/repo/tree/main/skills/my-skill', 'owner', 'repo', 'skills/my-skill'],
    'GitHub URL pointing at a SKILL.md file' => ['https://github.com/owner/repo/blob/main/skills/my-skill/SKILL.md', 'owner', 'repo', 'skills/my-skill'],
    'path with a trailing slash' => ['owner/repo/skills/my-skill/', 'owner', 'repo', 'skills/my-skill'],
    'path pointing at a SKILL.md file' => ['owner/repo/skills/my-skill/SKILL.md', 'owner', 'repo', 'skills/my-skill'],
    'repository root SKILL.md' => ['owner/repo/SKILL.md', 'owner', 'repo', ''],
    'repository named tree' => ['https://github.com/owner/tree/tree/main/skills', 'owner', 'tree', 'skills'],
    'repository named blob' => ['https://github.com/owner/blob/blob/main/skills/my-skill', 'owner', 'blob', 'skills/my-skill'],
]);

it('throws for invalid input', function (string $input, string $message): void {
    expect(fn (): GitHubRepository => GitHubRepository::fromInput($input))
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

it('returns source with path when present', function (): void {
    $repo = new GitHubRepository('owner', 'repo', 'path/to/skills');

    expect($repo->source())->toBe('owner/repo/path/to/skills');
});

it('keeps the branch named in a GitHub URL', function (string $input, string $branch, string $path): void {
    $result = GitHubRepository::fromInput($input);

    expect($result->branch)->toBe($branch)
        ->and($result->path)->toBe($path);
})->with([
    'no branch in plain input' => ['owner/repo/skills', '', 'skills'],
    'no branch in a plain URL' => ['https://github.com/owner/repo', '', ''],
    'default branch in a tree URL' => ['https://github.com/owner/repo/tree/main/skills', 'main', 'skills'],
    'other branch in a tree URL' => ['https://github.com/owner/repo/tree/develop/skills/my-skill', 'develop', 'skills/my-skill'],
    'branch in a blob URL' => ['https://github.com/owner/repo/blob/develop/skills/my-skill/SKILL.md', 'develop', 'skills/my-skill'],
    'branch with no path after it' => ['https://github.com/owner/repo/tree/develop', 'develop', ''],
]);

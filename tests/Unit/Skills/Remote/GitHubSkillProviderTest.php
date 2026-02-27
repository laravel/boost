<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Laravel\Boost\Skills\Remote\GitHubRepository;
use Laravel\Boost\Skills\Remote\GitHubSkillProvider;
use Laravel\Boost\Skills\Remote\RemoteSkill;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

function fakeGitHubRepo(string $branch = 'main'): array
{
    return ['api.github.com/repos/owner/repo' => Http::response(['default_branch' => $branch])];
}

function fakeTreeResponse(array $tree, string $branch = 'main'): array
{
    return [
        "api.github.com/repos/owner/repo/git/trees/{$branch}?recursive=1" => Http::response([
            'sha' => 'abc123',
            'url' => 'https://api.github.com/repos/owner/repo/git/trees/abc123',
            'tree' => $tree,
            'truncated' => false,
        ]),
    ];
}

it('discovers skills from repository directories', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'mode' => '040000', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'skill-one/SKILL.md', 'mode' => '100644', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ['path' => 'skill-two', 'mode' => '040000', 'type' => 'tree', 'sha' => 'jkl'],
            ['path' => 'skill-two/SKILL.md', 'mode' => '100644', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
            ['path' => 'README.md', 'mode' => '100644', 'type' => 'blob', 'sha' => 'pqr', 'size' => 789],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(2)
        ->and($skills->has('skill-one'))->toBeTrue()
        ->and($skills->has('skill-two'))->toBeTrue()
        ->and($skills->get('skill-one'))->toBeInstanceOf(RemoteSkill::class)
        ->and($skills->get('skill-one')->name)->toBe('skill-one')
        ->and($skills->get('skill-two')->name)->toBe('skill-two');

    Http::assertSentCount(2);
});

it('skips directories without SKILL.md', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'valid-skill', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'valid-skill/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ['path' => 'no-skill-file', 'type' => 'tree', 'sha' => 'jkl'],
            ['path' => 'no-skill-file/README.md', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('valid-skill'))->toBeTrue()
        ->and($skills->has('no-skill-file'))->toBeFalse();
});

it('throws exception when api fails with 404', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response(
            ['message' => 'Not Found'],
            404
        ),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));

    expect(fn (): Collection => $fetcher->discoverSkills())
        ->toThrow(RuntimeException::class, 'Failed to fetch repository tree from GitHub: Not Found (HTTP 404)');
});

it('downloads skill files to target directory', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ['path' => 'skill-one/README.md', 'type' => 'blob', 'sha' => 'jkl', 'size' => 456],
        ]),
        'raw.githubusercontent.com/owner/repo/main/skill-one/SKILL.md' => Http::response('# SKILL Content'),
        'raw.githubusercontent.com/owner/repo/main/skill-one/README.md' => Http::response('# README Content'),
    ]);

    $skill = new RemoteSkill(
        name: 'skill-one',
        repo: 'owner/repo',
        path: 'skill-one'
    );

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $result = $fetcher->downloadSkill($skill, $targetDir);

    expect($result)->toBeTrue()
        ->and($targetDir.'/SKILL.md')->toBeFile()
        ->and($targetDir.'/README.md')->toBeFile()
        ->and(file_get_contents($targetDir.'/SKILL.md'))->toBe('# SKILL Content')
        ->and(file_get_contents($targetDir.'/README.md'))->toBe('# README Content');

    array_map(unlink(...), glob($targetDir.'/*'));
    rmdir($targetDir);
});

it('downloads nested directory structure', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ['path' => 'skill-one/examples', 'type' => 'tree', 'sha' => 'jkl'],
            ['path' => 'skill-one/examples/example.md', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
        ]),
        'raw.githubusercontent.com/owner/repo/main/skill-one/SKILL.md' => Http::response('# SKILL'),
        'raw.githubusercontent.com/owner/repo/main/skill-one/examples/example.md' => Http::response('# Example'),
    ]);

    $skill = new RemoteSkill(
        name: 'skill-one',
        repo: 'owner/repo',
        path: 'skill-one'
    );

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $result = $fetcher->downloadSkill($skill, $targetDir);

    expect($result)->toBeTrue()
        ->and($targetDir.'/SKILL.md')->toBeFile()
        ->and($targetDir.'/examples/example.md')->toBeFile();

    @unlink($targetDir.'/examples/example.md');
    @rmdir($targetDir.'/examples');
    @unlink($targetDir.'/SKILL.md');
    @rmdir($targetDir);
});

it('returns false when skill path not in tree', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'other-skill', 'type' => 'tree', 'sha' => 'def'],
        ]),
    ]);

    $skill = new RemoteSkill(
        name: 'skill-one',
        repo: 'owner/repo',
        path: 'skill-one'
    );

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $result = $fetcher->downloadSkill($skill, $targetDir);

    expect($result)->toBeFalse();

    @rmdir($targetDir);
});

it('handles empty repository', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toBeEmpty();
});

it('ignores files at root level', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'README.md', 'type' => 'blob', 'sha' => 'def', 'size' => 123],
            ['path' => 'LICENSE', 'type' => 'blob', 'sha' => 'ghi', 'size' => 456],
            ['path' => '.gitignore', 'type' => 'blob', 'sha' => 'jkl', 'size' => 789],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toBeEmpty();
});

it('caches tree for multiple operations', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
        ]),
        'raw.githubusercontent.com/*' => Http::response('# Content'),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));

    $skills = $fetcher->discoverSkills();
    $fetcher->downloadSkill($skills->first(), $targetDir);

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'git/trees'));

    $treeApiCalls = collect(Http::recorded())
        ->filter(fn ($record): bool => str_contains((string) $record[0]->url(), 'git/trees'))
        ->count();

    expect($treeApiCalls)->toBe(1);

    @unlink($targetDir.'/SKILL.md');
    @rmdir($targetDir);
});

it('handles truncated tree response', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [
                ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
                ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ],
            'truncated' => true,
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toBeInstanceOf(Collection::class)
        ->and($skills)->toHaveCount(1);
});

it('discovers skills in nested paths like .ai/skills', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => '.ai', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => '.ai/skills', 'type' => 'tree', 'sha' => 'bbb'],
            ['path' => '.ai/skills/my-skill', 'type' => 'tree', 'sha' => 'ccc'],
            ['path' => '.ai/skills/my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'ddd', 'size' => 123],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('my-skill'))->toBeTrue();
});

it('throws exception when rate limit is exceeded', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response(
            ['message' => 'API rate limit exceeded'],
            403,
            [
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) (time() + 3600),
            ]
        ),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));

    expect(fn (): Collection => $fetcher->discoverSkills())
        ->toThrow(RuntimeException::class, 'GitHub API rate limit exceeded');
});

it('throws exception on invalid response structure', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response(
            ['invalid' => 'structure'],
            200
        ),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));

    expect(fn (): Collection => $fetcher->discoverSkills())
        ->toThrow(RuntimeException::class, 'Invalid response structure from GitHub Tree API');
});

it('uses specified repository path when provided', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'custom/path', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => 'custom/path/my-skill', 'type' => 'tree', 'sha' => 'bbb'],
            ['path' => 'custom/path/my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'ccc', 'size' => 123],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo', 'custom/path'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('my-skill'))->toBeTrue()
        ->and($skills->get('my-skill')->path)->toBe('custom/path/my-skill');
});

it('uses boost.github.token for authentication when available', function (): void {
    config(['boost.github.token' => 'test-token-123']);

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $fetcher->discoverSkills();

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer test-token-123'));
});

it('discovers skills in resources/boost/skills path', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'resources', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => 'resources/boost', 'type' => 'tree', 'sha' => 'bbb'],
            ['path' => 'resources/boost/skills', 'type' => 'tree', 'sha' => 'ccc'],
            ['path' => 'resources/boost/skills/my-skill', 'type' => 'tree', 'sha' => 'ddd'],
            ['path' => 'resources/boost/skills/my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'eee', 'size' => 123],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('my-skill'))->toBeTrue()
        ->and($skills->get('my-skill')->path)->toBe('resources/boost/skills/my-skill');
});

it('resolves non-main default branch from github api', function (): void {
    Http::fake([
        ...fakeGitHubRepo('0.x'),
        ...fakeTreeResponse([
            ['path' => 'resources', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => 'resources/boost', 'type' => 'tree', 'sha' => 'bbb'],
            ['path' => 'resources/boost/skills', 'type' => 'tree', 'sha' => 'ccc'],
            ['path' => 'resources/boost/skills/my-skill', 'type' => 'tree', 'sha' => 'ddd'],
            ['path' => 'resources/boost/skills/my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'eee', 'size' => 123],
        ], '0.x'),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('my-skill'))->toBeTrue();

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'git/trees/0.x'));
});

it('uses services.github.token for authentication when boost.github.token is not set', function (): void {
    config(['services.github.token' => 'gh-token-456']);

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $fetcher->discoverSkills();

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer gh-token-456'));
});

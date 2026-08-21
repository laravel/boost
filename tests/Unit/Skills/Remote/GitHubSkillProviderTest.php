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

it('url-encodes branch names containing slashes', function (): void {
    Http::fake([
        ...fakeGitHubRepo('release/1.x'),
        'api.github.com/repos/owner/repo/git/trees/release%2F1.x?recursive=1' => Http::response([
            'sha' => 'abc123',
            'url' => 'https://api.github.com/repos/owner/repo/git/trees/abc123',
            'tree' => [
                ['path' => 'my-skill', 'type' => 'tree', 'sha' => 'aaa'],
                ['path' => 'my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'bbb', 'size' => 123],
            ],
            'truncated' => false,
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('my-skill'))->toBeTrue();
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

it('only discovers Markdown skill markers from remote repositories', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'skill-one/SKILL.blade.php', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ['path' => 'skill-two', 'type' => 'tree', 'sha' => 'jkl'],
            ['path' => 'skill-two/SKILL.md', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('skill-one'))->toBeFalse()
        ->and($skills->has('skill-two'))->toBeTrue()
        ->and($skills->get('skill-two')->name)->toBe('skill-two');
});

it('does not download Blade templates from remote skills', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ['path' => 'skill-one/references', 'type' => 'tree', 'sha' => 'jkl'],
            ['path' => 'skill-one/references/example.blade.php', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
        ]),
        'raw.githubusercontent.com/owner/repo/main/skill-one/SKILL.md' => Http::response('# SKILL Content'),
        'raw.githubusercontent.com/owner/repo/main/skill-one/references/example.blade.php' => Http::response('template'),
    ]);

    $skill = new RemoteSkill(
        name: 'skill-one',
        repo: 'owner/repo',
        path: 'skill-one'
    );

    try {
        $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
        $result = $fetcher->downloadSkill($skill, $targetDir);

        expect($result)->toBeTrue()
            ->and($targetDir.'/SKILL.md')->toBeFile()
            ->and($targetDir.'/references/example.blade.php')->not->toBeFile();

        Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '.blade.php'));
    } finally {
        @unlink($targetDir.'/references/example.blade.php');
        @rmdir($targetDir.'/references');
        @unlink($targetDir.'/SKILL.md');
        @rmdir($targetDir);
    }
});

it('does not download PHP files whatever their casing', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ['path' => 'skill-one/SKILL.Blade.php', 'type' => 'blob', 'sha' => 'jkl', 'size' => 456],
            ['path' => 'skill-one/payload.PHP', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
        ]),
        'raw.githubusercontent.com/owner/repo/main/skill-one/SKILL.md' => Http::response('# SKILL Content'),
    ]);

    $skill = new RemoteSkill(name: 'skill-one', repo: 'owner/repo', path: 'skill-one');

    try {
        $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));

        expect($fetcher->downloadSkill($skill, $targetDir))->toBeTrue()
            ->and($targetDir.'/SKILL.md')->toBeFile();

        Http::assertNotSent(fn ($request): bool => str_contains(strtolower((string) $request->url()), '.php'));
    } finally {
        @unlink($targetDir.'/SKILL.md');
        @rmdir($targetDir);
    }
});

it('discovers skills in wildcard paths like .ai/*/skills', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => '.ai', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => '.ai/claude', 'type' => 'tree', 'sha' => 'bbb'],
            ['path' => '.ai/claude/skills', 'type' => 'tree', 'sha' => 'ccc'],
            ['path' => '.ai/claude/skills/my-skill', 'type' => 'tree', 'sha' => 'ddd'],
            ['path' => '.ai/claude/skills/my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'eee', 'size' => 123],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('my-skill'))->toBeTrue()
        ->and($skills->get('my-skill')->path)->toBe('.ai/claude/skills/my-skill');
});

it('ignores a SKILL.md at the repository root', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'SKILL.md', 'type' => 'blob', 'sha' => 'aaa', 'size' => 123],
            ['path' => 'README.md', 'type' => 'blob', 'sha' => 'bbb', 'size' => 456],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));

    // A root SKILL.md would otherwise be named "." and resolve to the skills directory itself.
    expect($fetcher->discoverSkills())->toBeEmpty();
});

it('still finds nested skills when the repository root also has a SKILL.md', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'SKILL.md', 'type' => 'blob', 'sha' => 'aaa', 'size' => 123],
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'bbb'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ccc', 'size' => 456],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->keys()->all())->toBe(['skill-one']);
});

it('discovers a skill when the path points at the skill directory itself', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skills', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => 'skills/my-skill', 'type' => 'tree', 'sha' => 'bbb'],
            ['path' => 'skills/my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'ccc', 'size' => 123],
            ['path' => 'skills/other-skill', 'type' => 'tree', 'sha' => 'ddd'],
            ['path' => 'skills/other-skill/SKILL.md', 'type' => 'blob', 'sha' => 'eee', 'size' => 456],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo', 'skills/my-skill'));
    $skills = $fetcher->discoverSkills();

    expect($skills)->toHaveCount(1)
        ->and($skills->has('my-skill'))->toBeTrue()
        ->and($skills->get('my-skill')->path)->toBe('skills/my-skill');
});

it('downloads a skill whose path was given directly', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skills', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => 'skills/my-skill', 'type' => 'tree', 'sha' => 'bbb'],
            ['path' => 'skills/my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'ccc', 'size' => 123],
        ]),
        'raw.githubusercontent.com/owner/repo/main/skills/my-skill/SKILL.md' => Http::response('# Direct'),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo', 'skills/my-skill'));
    $skill = $fetcher->discoverSkills()->get('my-skill');

    try {
        expect($fetcher->downloadSkill($skill, $targetDir))->toBeTrue()
            ->and(file_get_contents($targetDir.'/SKILL.md'))->toBe('# Direct');
    } finally {
        array_map(unlink(...), glob($targetDir.'/*'));
        rmdir($targetDir);
    }
});

it('ignores a skill directory whose name cannot be a skill name', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => '... ', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => '... /SKILL.md', 'type' => 'blob', 'sha' => 'bbb', 'size' => 123],
            ['path' => '..\\..\\evil', 'type' => 'tree', 'sha' => 'eee'],
            ['path' => '..\\..\\evil/SKILL.md', 'type' => 'blob', 'sha' => 'fff', 'size' => 789],
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'ccc'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ddd', 'size' => 456],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));

    expect($fetcher->discoverSkills()->keys()->all())->toBe(['skill-one']);
});

it('discovers skills at any depth below the given path', function (string $path): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => '.ai', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => '.ai/claude', 'type' => 'tree', 'sha' => 'bbb'],
            ['path' => '.ai/claude/skills', 'type' => 'tree', 'sha' => 'ccc'],
            ['path' => '.ai/claude/skills/my-skill', 'type' => 'tree', 'sha' => 'ddd'],
            ['path' => '.ai/claude/skills/my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'eee', 'size' => 123],
        ]),
    ]);

    $skills = (new GitHubSkillProvider(new GitHubRepository('owner', 'repo', $path)))->discoverSkills();

    expect($skills->keys()->all())->toBe(['my-skill'])
        ->and($skills->get('my-skill')->path)->toBe('.ai/claude/skills/my-skill');
})->with([
    'whole repository' => [''],
    'a distant parent' => ['.ai'],
    'an intermediate parent' => ['.ai/claude'],
    'the direct parent' => ['.ai/claude/skills'],
    'the skill directory itself' => ['.ai/claude/skills/my-skill'],
    'the skill directory with a trailing slash' => ['.ai/claude/skills/my-skill/'],
    'the SKILL.md file itself' => ['.ai/claude/skills/my-skill/SKILL.md'],
]);

it('keeps skills apart when one path is a prefix of another', function (): void {
    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skills/my-skill', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => 'skills/my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'bbb', 'size' => 123],
            ['path' => 'skills/my-skill-extra', 'type' => 'tree', 'sha' => 'ccc'],
            ['path' => 'skills/my-skill-extra/SKILL.md', 'type' => 'blob', 'sha' => 'ddd', 'size' => 456],
        ]),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo', 'skills/my-skill'));

    expect($fetcher->discoverSkills()->keys()->all())->toBe(['my-skill']);
});

it('reads the branch named in the repository instead of asking for the default', function (): void {
    Http::fake(fakeTreeResponse([
        ['path' => 'my-skill', 'type' => 'tree', 'sha' => 'aaa'],
        ['path' => 'my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'bbb', 'size' => 123],
    ], 'develop'));

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo', 'my-skill', 'develop'));

    expect($fetcher->discoverSkills()->keys()->all())->toBe(['my-skill']);

    Http::assertNotSent(fn ($request): bool => (string) $request->url() === 'https://api.github.com/repos/owner/repo');
});

it('downloads a skill from the branch named in the repository', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeTreeResponse([
            ['path' => 'my-skill', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => 'my-skill/SKILL.md', 'type' => 'blob', 'sha' => 'bbb', 'size' => 123],
        ], 'develop'),
        'raw.githubusercontent.com/owner/repo/develop/my-skill/SKILL.md' => Http::response('# Develop'),
    ]);

    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo', 'my-skill', 'develop'));
    $skill = $fetcher->discoverSkills()->get('my-skill');

    try {
        expect($fetcher->downloadSkill($skill, $targetDir))->toBeTrue()
            ->and(file_get_contents($targetDir.'/SKILL.md'))->toBe('# Develop');
    } finally {
        array_map(unlink(...), glob($targetDir.'/*'));
        rmdir($targetDir);
    }
});

it('does not write a skill file whose repository path contains a backslash', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'bbb', 'size' => 123],
            ['path' => 'skill-one/..\\..\\escaped.txt', 'type' => 'blob', 'sha' => 'ccc', 'size' => 456],
        ]),
        'raw.githubusercontent.com/owner/repo/main/skill-one/SKILL.md' => Http::response('# Skill'),
        '*' => Http::response('escaped'),
    ]);

    $skill = new RemoteSkill(name: 'skill-one', repo: 'owner/repo', path: 'skill-one');
    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));

    try {
        // Windows reads the backslash as a separator, so this path would land outside the skill directory.
        expect($fetcher->downloadSkill($skill, $targetDir))->toBeTrue()
            ->and(array_values(array_diff(scandir($targetDir), ['.', '..'])))->toBe(['SKILL.md']);
    } finally {
        array_map(unlink(...), glob($targetDir.'/*') ?: []);
        rmdir($targetDir);
    }
});

it('does not accept a backslash path as the required SKILL.md', function (): void {
    $targetDir = sys_get_temp_dir().'/boost-test-'.uniqid();

    Http::fake([
        ...fakeGitHubRepo(),
        ...fakeTreeResponse([
            ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'aaa'],
            ['path' => 'skill-one/nested\\SKILL.md', 'type' => 'blob', 'sha' => 'bbb', 'size' => 123],
        ]),
        '*' => Http::response('# Skill'),
    ]);

    $skill = new RemoteSkill(name: 'skill-one', repo: 'owner/repo', path: 'skill-one');
    $fetcher = new GitHubSkillProvider(new GitHubRepository('owner', 'repo'));

    expect($fetcher->downloadSkill($skill, $targetDir))->toBeFalse()
        ->and(is_dir($targetDir))->toBeFalse();
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

uses(InteractsWithPublishedFiles::class);

beforeEach(function (): void {
    File::deleteDirectory(base_path('.ai/skills'));

    $this->files = [
        '.ai/skills/skill-one/SKILL.md',
        '.ai/skills/skill-one/examples/example.md',
        '.ai/skills/skill-two/SKILL.md',
    ];
});

it('throws exception for invalid repository format', function (): void {
    $this->artisan('boost:add-skill', ['repo' => 'invalid-format']);
})->throws(InvalidArgumentException::class, 'Invalid repository format');

it('lists available skills with --list option', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [
                ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
                ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
                ['path' => 'skill-two', 'type' => 'tree', 'sha' => 'jkl'],
                ['path' => 'skill-two/SKILL.md', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
            ],
            'truncated' => false,
        ]),
    ]);

    $this->artisan('boost:add-skill', ['repo' => 'owner/repo', '--list' => true])
        ->assertSuccessful();
});

it('shows error when no skills found', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [],
            'truncated' => false,
        ]),
    ]);

    $this->artisan('boost:add-skill', ['repo' => 'owner/repo'])
        ->assertFailed()
        ->expectsOutputToContain('No valid skills are found');
});

it('shows error when api request fails', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response(
            ['message' => 'Not Found'],
            404
        ),
    ]);

    $this->artisan('boost:add-skill', ['repo' => 'owner/repo'])
        ->assertFailed()
        ->expectsOutputToContain('Failed to fetch repository tree from GitHub');
});

it('installs all skills with --all option', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [
                ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
                ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ],
            'truncated' => false,
        ]),
        'raw.githubusercontent.com/*' => Http::response(<<<'YAML'
            ---
            name: skill-one
            description: First skill
            ---
            # SKILL Content
            YAML),
    ]);

    $this->artisan('boost:add-skill', [
        'repo' => 'owner/repo',
        '--all' => true,
    ])->assertSuccessful();

    $this->assertFilenameExists('.ai/skills/skill-one/SKILL.md');
    $this->assertFileContains(['# SKILL Content'], '.ai/skills/skill-one/SKILL.md');
});

it('installs specific skills with --skill option', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [
                ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
                ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
                ['path' => 'skill-two', 'type' => 'tree', 'sha' => 'jkl'],
                ['path' => 'skill-two/SKILL.md', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
            ],
            'truncated' => false,
        ]),
        'raw.githubusercontent.com/*skill-one*' => Http::response(<<<'YAML'
            ---
            name: skill-one
            description: First skill
            ---
            # SKILL Content
            YAML),
    ]);

    $this->artisan('boost:add-skill', [
        'repo' => 'owner/repo',
        '--skill' => ['skill-one'],
    ])->assertSuccessful();

    $this->assertFilenameExists('.ai/skills/skill-one/SKILL.md');
    $this->assertFilenameNotExists('.ai/skills/skill-two/SKILL.md');
});

it('skips existing skills without --force flag', function (): void {
    File::ensureDirectoryExists(base_path('.ai/skills/skill-one'));
    File::put(base_path('.ai/skills/skill-one/SKILL.md'), 'existing content');

    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [
                ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
                ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ],
            'truncated' => false,
        ]),
    ]);

    $this->artisan('boost:add-skill', [
        'repo' => 'owner/repo',
        '--all' => true,
    ])->assertSuccessful();

    $this->assertFileContains(['existing content'], '.ai/skills/skill-one/SKILL.md');
});

it('overwrites existing skills with --force flag', function (): void {
    File::ensureDirectoryExists(base_path('.ai/skills/skill-one'));
    File::put(base_path('.ai/skills/skill-one/SKILL.md'), 'existing content');

    $newContent = <<<'YAML'
        ---
        name: skill-one
        description: First skill
        ---
        # New Content
        YAML;

    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [
                ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
                ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ],
            'truncated' => false,
        ]),
        'raw.githubusercontent.com/*' => Http::response($newContent),
    ]);

    $this->artisan('boost:add-skill', [
        'repo' => 'owner/repo',
        '--all' => true,
        '--force' => true,
    ])->assertSuccessful();

    $this->assertFileContains(['# New Content'], '.ai/skills/skill-one/SKILL.md');
    $this->assertFileNotContains(['existing content'], '.ai/skills/skill-one/SKILL.md');
});

it('installs nested skill files correctly', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [
                ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
                ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
                ['path' => 'skill-one/examples', 'type' => 'tree', 'sha' => 'jkl'],
                ['path' => 'skill-one/examples/example.md', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
            ],
            'truncated' => false,
        ]),
        'raw.githubusercontent.com/*SKILL.md' => Http::response(<<<'YAML'
            ---
            name: skill-one
            description: First skill
            ---
            # SKILL
            YAML),
        'raw.githubusercontent.com/*example.md' => Http::response('# Example content'),
    ]);

    $this->artisan('boost:add-skill', [
        'repo' => 'owner/repo',
        '--all' => true,
    ])->assertSuccessful();

    $this->assertFilenameExists('.ai/skills/skill-one/SKILL.md');
    $this->assertFilenameExists('.ai/skills/skill-one/examples/example.md');
    $this->assertFileContains(['# SKILL'], '.ai/skills/skill-one/SKILL.md');
    $this->assertFileContains(['# Example content'], '.ai/skills/skill-one/examples/example.md');
});

it('shows success message after installing skills', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [
                ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
                ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
            ],
            'truncated' => false,
        ]),
        'raw.githubusercontent.com/*' => Http::response(<<<'YAML'
            ---
            name: skill-one
            description: First skill
            ---
            # SKILL Content
            YAML),
    ]);

    $this->artisan('boost:add-skill', [
        'repo' => 'owner/repo',
        '--all' => true,
    ])
        ->expectsOutputToContain('Skills installed')
        ->assertSuccessful();
});

it('shows available skill count when listing', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response([
            'sha' => 'abc123',
            'tree' => [
                ['path' => 'skill-one', 'type' => 'tree', 'sha' => 'def'],
                ['path' => 'skill-one/SKILL.md', 'type' => 'blob', 'sha' => 'ghi', 'size' => 123],
                ['path' => 'skill-two', 'type' => 'tree', 'sha' => 'jkl'],
                ['path' => 'skill-two/SKILL.md', 'type' => 'blob', 'sha' => 'mno', 'size' => 456],
            ],
            'truncated' => false,
        ]),
    ]);

    $this->artisan('boost:add-skill', ['repo' => 'owner/repo', '--list' => true])
        ->expectsOutputToContain('Found 2 available skills')
        ->assertSuccessful();
});

it('displays error when rate limit is exceeded', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/git/trees/main?recursive=1' => Http::response(
            ['message' => 'API rate limit exceeded'],
            403,
            [
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) (time() + 3600),
            ]
        ),
    ]);

    $this->artisan('boost:add-skill', ['repo' => 'owner/repo'])
        ->assertFailed()
        ->expectsOutputToContain('GitHub API rate limit exceeded');
});

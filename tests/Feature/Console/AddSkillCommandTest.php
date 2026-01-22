<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

uses(InteractsWithPublishedFiles::class);

beforeEach(function (): void {
    // Trait handles file cleanup, but we need directory cleanup too
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
        'api.github.com/*' => Http::response([
            ['name' => 'skill-one', 'path' => 'skill-one', 'type' => 'dir'],
            ['name' => 'skill-two', 'path' => 'skill-two', 'type' => 'dir'],
        ]),
        'raw.githubusercontent.com/*skill-one*' => Http::response(<<<'YAML'
            ---
            name: skill-one
            description: First skill
            ---
            # Content
            YAML),
        'raw.githubusercontent.com/*skill-two*' => Http::response(<<<'YAML'
            ---
            name: skill-two
            description: Second skill
            ---
            # Content
            YAML),
    ]);

    $this->artisan('boost:add-skill', ['repo' => 'owner/repo', '--list' => true])
        ->assertSuccessful();
});

it('shows error when no skills found', function (): void {
    Http::fake([
        'api.github.com/*' => Http::response([]),
    ]);

    $this->artisan('boost:add-skill', ['repo' => 'owner/repo'])
        ->assertFailed()
        ->expectsOutputToContain('No valid skills are found');
});

it('shows error when api request fails', function (): void {
    Http::fake([
        '*' => Http::response(null, 404),
    ]);

    $this->artisan('boost:add-skill', ['repo' => 'owner/repo'])
        ->assertFailed()
        ->expectsOutputToContain('No valid skills are found');
});

it('installs all skills with --all option', function (): void {
    Http::fake([
        'api.github.com/repos/owner/repo/contents/' => Http::response([
            ['name' => 'skill-one', 'path' => 'skill-one', 'type' => 'dir'],
        ]),
        'api.github.com/repos/owner/repo/contents/skill-one' => Http::response([
            ['name' => 'SKILL.md', 'path' => 'skill-one/SKILL.md', 'type' => 'file'],
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
        'api.github.com/repos/owner/repo/contents/' => Http::response([
            ['name' => 'skill-one', 'path' => 'skill-one', 'type' => 'dir'],
            ['name' => 'skill-two', 'path' => 'skill-two', 'type' => 'dir'],
        ]),
        'api.github.com/repos/owner/repo/contents/skill-one' => Http::response([
            ['name' => 'SKILL.md', 'path' => 'skill-one/SKILL.md', 'type' => 'file'],
        ]),
        'raw.githubusercontent.com/*skill-one*' => Http::response(<<<'YAML'
            ---
            name: skill-one
            description: First skill
            ---
            # SKILL Content
            YAML),
        'raw.githubusercontent.com/*skill-two*' => Http::response(<<<'YAML'
            ---
            name: skill-two
            description: Second skill
            ---
            # Content
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
        'api.github.com/*' => Http::response([
            ['name' => 'skill-one', 'path' => 'skill-one', 'type' => 'dir'],
        ]),
        'raw.githubusercontent.com/*' => Http::response(<<<'YAML'
            ---
            name: skill-one
            description: First skill
            ---
            # Content
            YAML),
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
        'api.github.com/repos/owner/repo/contents/' => Http::response([
            ['name' => 'skill-one', 'path' => 'skill-one', 'type' => 'dir'],
        ]),
        'api.github.com/repos/owner/repo/contents/skill-one' => Http::response([
            ['name' => 'SKILL.md', 'path' => 'skill-one/SKILL.md', 'type' => 'file'],
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
        'api.github.com/repos/owner/repo/contents/' => Http::response([
            ['name' => 'skill-one', 'path' => 'skill-one', 'type' => 'dir'],
        ]),
        'api.github.com/repos/owner/repo/contents/skill-one' => Http::response([
            ['name' => 'SKILL.md', 'path' => 'skill-one/SKILL.md', 'type' => 'file'],
            ['name' => 'examples', 'path' => 'skill-one/examples', 'type' => 'dir'],
        ]),
        'api.github.com/repos/owner/repo/contents/skill-one/examples' => Http::response([
            ['name' => 'example.md', 'path' => 'skill-one/examples/example.md', 'type' => 'file'],
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
        'api.github.com/repos/owner/repo/contents/' => Http::response([
            ['name' => 'skill-one', 'path' => 'skill-one', 'type' => 'dir'],
        ]),
        'api.github.com/repos/owner/repo/contents/skill-one' => Http::response([
            ['name' => 'SKILL.md', 'path' => 'skill-one/SKILL.md', 'type' => 'file'],
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
        'api.github.com/*' => Http::response([
            ['name' => 'skill-one', 'path' => 'skill-one', 'type' => 'dir'],
            ['name' => 'skill-two', 'path' => 'skill-two', 'type' => 'dir'],
        ]),
        'raw.githubusercontent.com/*skill-one*' => Http::response(<<<'YAML'
            ---
            name: skill-one
            description: First skill
            ---
            # Content
            YAML),
        'raw.githubusercontent.com/*skill-two*' => Http::response(<<<'YAML'
            ---
            name: skill-two
            description: Second skill
            ---
            # Content
            YAML),
    ]);

    $this->artisan('boost:add-skill', ['repo' => 'owner/repo', '--list' => true])
        ->expectsOutputToContain('Found 2 available skills')
        ->assertSuccessful();
});

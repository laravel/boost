<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::deleteDirectory(base_path('.ai/skills'));
});

it('lists available skills', function (): void {
    File::ensureDirectoryExists(base_path('.ai/skills/skill-one'));
    file_put_contents(base_path('.ai/skills/skill-one/SKILL.md'), "---\nname: skill-one\ndescription: First skill\n---\n\n# Skill One Content\n");

    File::ensureDirectoryExists(base_path('.ai/skills/skill-two'));
    file_put_contents(base_path('.ai/skills/skill-two/SKILL.md'), "---\nname: skill-two\ndescription: Second skill\n---\n\n# Skill Two Content\n");

    $this->artisan('boost:skills-list')
        ->assertSuccessful()
        ->expectsOutputToContain('Found 2 skills');
});

it('shows message when no skills available', function (): void {
    $this->artisan('boost:skills-list')
        ->assertSuccessful()
        ->expectsOutputToContain('No skills available in this project.');
});

it('outputs skills as json with --json option', function (): void {
    $skillPath = base_path('.ai/skills/test-skill');
    File::ensureDirectoryExists($skillPath);
    file_put_contents($skillPath.'/SKILL.md', "---\nname: test-skill\ndescription: A test skill\n---\n\n# Test Skill Content\n");

    $this->artisan('boost:skills-list', ['--json' => true])
        ->assertSuccessful();
});

it('displays user-defined skills with asterisk suffix', function (): void {
    File::ensureDirectoryExists(base_path('.ai/skills/my-custom-skill'));
    file_put_contents(base_path('.ai/skills/my-custom-skill/SKILL.md'), "---\nname: my-custom-skill\ndescription: My custom skill\n---\n\n# Content\n");

    $this->artisan('boost:skills-list')
        ->assertSuccessful()
        ->expectsOutputToContain('* = user-defined skill');
});

it('shows local label for user-defined skills', function (): void {
    File::ensureDirectoryExists(base_path('.ai/skills/user-skill'));
    file_put_contents(base_path('.ai/skills/user-skill/SKILL.md'), "---\nname: user-skill\ndescription: User defined skill\n---\n\n# Content\n");

    $this->artisan('boost:skills-list')
        ->assertSuccessful()
        ->expectsOutputToContain('local');
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

uses(InteractsWithPublishedFiles::class);

beforeEach(function (): void {
    File::deleteDirectory(base_path('.ai/skills'));
    File::ensureDirectoryExists(base_path('.ai/skills/skill-one'));
    File::put(base_path('.ai/skills/skill-one/SKILL.md'), 'content');
    File::ensureDirectoryExists(base_path('.ai/skills/skill-two'));
    File::put(base_path('.ai/skills/skill-two/SKILL.md'), 'content');
});

it('removes a single skill', function (): void {
    $this->artisan('boost:rm-skill', [
        'skills' => ['skill-one'],
        '--force' => true,
    ])
        ->expectsOutput('Skills removed: skill-one')
        ->assertSuccessful();

    $this->assertFilenameNotExists('.ai/skills/skill-one');
    $this->assertFilenameExists('.ai/skills/skill-two');
});

it('removes multiple skills', function (): void {
    $this->artisan('boost:rm-skill', [
        'skills' => ['skill-one', 'skill-two'],
        '--force' => true,
    ])
        ->expectsOutput('Skills removed: skill-one, skill-two')
        ->assertSuccessful();

    $this->assertFilenameNotExists('.ai/skills/skill-one');
    $this->assertFilenameNotExists('.ai/skills/skill-two');
});

it('cancels removal when confirmation is denied', function (): void {
    if (! stream_isatty(STDIN)) {
        $this->markTestSkipped('Interactive test skipped in non-TTY environment.');
    }

    $this->artisan('boost:rm-skill', ['skills' => ['skill-one']])
        ->expectsConfirmation("Are you sure you want to remove the 'skill-one' skill?", 'no')
        ->expectsOutput('Removal cancelled.')
        ->assertSuccessful();

    $this->assertFilenameExists('.ai/skills/skill-one/SKILL.md');
});

it('fails to remove a non-existent skill', function (): void {
    $this->artisan('boost:rm-skill', ['skills' => ['non-existent-skill']])
        ->expectsOutput("Skill 'non-existent-skill' not found.")
        ->expectsOutput('No valid skills to remove.')
        ->assertFailed();
});

it('prompts for skill selection if no argument is provided', function (): void {
    if (! stream_isatty(STDIN)) {
        $this->markTestSkipped('Interactive test skipped in non-TTY environment.');
    }

    $this->artisan('boost:rm-skill')
        ->expectsChoice('Which skills would you like to remove?', ['skill-one'], ['skill-one' => 'skill-one', 'skill-two' => 'skill-two'])
        ->expectsConfirmation("Are you sure you want to remove the 'skill-one' skill?", 'yes')
        ->expectsOutput('Skills removed: skill-one')
        ->assertSuccessful();

    $this->assertFilenameNotExists('.ai/skills/skill-one');
    $this->assertFilenameExists('.ai/skills/skill-two');
});

it('displays error when no skills are installed when prompting', function (): void {
    File::deleteDirectory(base_path('.ai/skills'));
    File::ensureDirectoryExists(base_path('.ai/skills'));

    $this->artisan('boost:rm-skill')
        ->expectsOutput('No skills installed.')
        ->assertSuccessful();
});

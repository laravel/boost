<?php

declare(strict_types=1);

use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillWriter;

function cleanupSkillDirectory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
    }

    @rmdir($path);
}

it('writes skill to a target directory', function (): void {
    $sourceDir = fixture('skills/test-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skill = new Skill(
        name: 'test-skill',
        package: 'boost',
        path: $sourceDir,
        description: 'Test skill',
    );

    $writer = new SkillWriter($agent);
    $result = $writer->write($skill);

    expect($result)->toBe(SkillWriter::SUCCESS)
        ->and($absoluteTarget.'/test-skill')->toBeDirectory()
        ->and($absoluteTarget.'/test-skill/SKILL.md')->toBeFile()
        ->and($absoluteTarget.'/test-skill/references/example.md')->toBeFile();

    cleanupSkillDirectory($absoluteTarget);
});

it('returns UPDATED when skill directory already exists', function (): void {
    $sourceDir = fixture('skills/test-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);
    $targetSkill = $absoluteTarget.'/test-skill';
    mkdir($targetSkill, 0755, true);
    file_put_contents($targetSkill.'/SKILL.md', 'old content');

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skill = new Skill(
        name: 'test-skill',
        package: 'boost',
        path: $sourceDir,
        description: 'Test skill',
    );

    $writer = new SkillWriter($agent);
    $result = $writer->write($skill);

    expect($result)->toBe(SkillWriter::UPDATED);

    $content = file_get_contents($targetSkill.'/SKILL.md');
    expect($content)->toContain('name: test-skill');

    cleanupSkillDirectory($absoluteTarget);
});

it('returns FAILED when source directory does not exist', function (): void {
    $relativeTarget = '.boost-test-skills-'.uniqid();

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skill = new Skill(
        name: 'missing-skill',
        package: 'boost',
        path: '/nonexistent/path/'.uniqid(),
        description: 'Missing skill',
    );

    $writer = new SkillWriter($agent);
    $result = $writer->write($skill);

    expect($result)->toBe(SkillWriter::FAILED);
});

it('writes all skills', function (): void {
    $sourceDir = fixture('skills/test-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skills = collect([
        new Skill('skill-one', 'boost', $sourceDir, 'First skill'),
        new Skill('skill-two', 'boost', $sourceDir, 'Second skill'),
    ]);

    $writer = new SkillWriter($agent);
    $results = $writer->writeAll($skills);

    expect($results)->toHaveCount(2)
        ->and($results['skill-one'])->toBe(SkillWriter::SUCCESS)
        ->and($results['skill-two'])->toBe(SkillWriter::SUCCESS);

    cleanupSkillDirectory($absoluteTarget);
});

it('copies nested directory structure', function (): void {
    $sourceDir = fixture('skills/nested-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skill = new Skill(
        name: 'nested-skill',
        package: 'boost',
        path: $sourceDir,
        description: 'Nested skill',
    );

    $writer = new SkillWriter($agent);
    $result = $writer->write($skill);

    expect($result)->toBe(SkillWriter::SUCCESS)
        ->and($absoluteTarget.'/nested-skill/SKILL.md')->toBeFile()
        ->and($absoluteTarget.'/nested-skill/references/ref.md')->toBeFile()
        ->and($absoluteTarget.'/nested-skill/references/deep/nested/file.md')->toBeFile();

    cleanupSkillDirectory($absoluteTarget);
});

it('throws an exception for path traversal in skill name', function (string $maliciousName): void {
    $sourceDir = fixture('skills/test-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skill = new Skill(
        name: $maliciousName,
        package: 'boost',
        path: $sourceDir,
        description: 'Malicious skill',
    );

    $writer = new SkillWriter($agent);

    expect(fn (): int => $writer->write($skill))
        ->toThrow(RuntimeException::class, 'Invalid skill name');
})->with([
    '../../../etc/passwd',
    '../../.bashrc',
    'skill/with/slash',
    'skill\\with\\backslash',
    '../parent',
]);

it('renders blade templates to markdown', function (): void {
    $sourceDir = fixture('skills/blade-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skill = new Skill(
        name: 'blade-skill',
        package: 'boost',
        path: $sourceDir,
        description: 'Blade skill',
    );

    $writer = new SkillWriter($agent);
    $result = $writer->write($skill);

    expect($result)->toBe(SkillWriter::SUCCESS)
        ->and($absoluteTarget.'/blade-skill/SKILL.md')->toBeFile()
        ->and($absoluteTarget.'/blade-skill/references/ref.md')->toBeFile();

    $content = file_get_contents($absoluteTarget.'/blade-skill/SKILL.md');
    expect($content)->toContain('The answer is 2')
        ->not->toContain('{{ 1 + 1 }}');

    cleanupSkillDirectory($absoluteTarget);
});

it('preserves vue template syntax in verbatim blocks when rendering blade skills', function (): void {
    $sourceDir = fixture('skills/vue-syntax-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skill = new Skill(
        name: 'vue-syntax-skill',
        package: 'boost',
        path: $sourceDir,
        description: 'Vue syntax test skill',
    );

    $writer = new SkillWriter($agent);
    $result = $writer->write($skill);

    expect($result)->toBe(SkillWriter::SUCCESS)
        ->and($absoluteTarget.'/vue-syntax-skill/SKILL.md')->toBeFile();

    $content = file_get_contents($absoluteTarget.'/vue-syntax-skill/SKILL.md');

    // Vue template syntax should be preserved correctly (not escaped with @)
    expect($content)
        ->toContain('{{ user.name }}')
        ->toContain('{{ errors.email }}')
        // Should NOT contain Blade-escaped syntax
        ->not->toContain('@{{ user.name }}')
        ->not->toContain('@{{ errors.email }}');

    cleanupSkillDirectory($absoluteTarget);
});

it('removes a skill directory', function (): void {
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);
    $skillDir = $absoluteTarget.'/test-skill';

    mkdir($skillDir, 0755, true);
    file_put_contents($skillDir.'/SKILL.md', 'test content');

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $writer = new SkillWriter($agent);
    $result = $writer->remove('test-skill');

    expect($result)->toBeTrue()
        ->and($skillDir)->not->toBeDirectory();

    cleanupSkillDirectory($absoluteTarget);
});

it('returns true when removing a non-existent skill', function (): void {
    $relativeTarget = '.boost-test-skills-'.uniqid();

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $writer = new SkillWriter($agent);
    $result = $writer->remove('nonexistent-skill');

    expect($result)->toBeTrue();
});

it('returns false when removing skill with invalid name', function (): void {
    $relativeTarget = '.boost-test-skills-'.uniqid();

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $writer = new SkillWriter($agent);

    expect($writer->remove('../malicious'))->toBeFalse()
        ->and($writer->remove('skill/with/slash'))->toBeFalse();
});

it('removes multiple stale skills', function (): void {
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $skillOneDir = $absoluteTarget.'/skill-one';
    $skillTwoDir = $absoluteTarget.'/skill-two';
    $skillThreeDir = $absoluteTarget.'/skill-three';

    mkdir($skillOneDir, 0755, true);
    mkdir($skillTwoDir, 0755, true);
    mkdir($skillThreeDir, 0755, true);

    file_put_contents($skillOneDir.'/SKILL.md', 'skill one');
    file_put_contents($skillTwoDir.'/SKILL.md', 'skill two');
    file_put_contents($skillThreeDir.'/SKILL.md', 'skill three');

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $writer = new SkillWriter($agent);
    $results = $writer->removeStale(['skill-one', 'skill-two']);

    expect($results)->toHaveCount(2)
        ->and($results['skill-one'])->toBeTrue()
        ->and($results['skill-two'])->toBeTrue()
        ->and($skillOneDir)->not->toBeDirectory()
        ->and($skillTwoDir)->not->toBeDirectory()
        ->and($skillThreeDir)->toBeDirectory();

    cleanupSkillDirectory($absoluteTarget);
});

it('removes nested skill directory with deep structure', function (): void {
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);
    $skillDir = $absoluteTarget.'/nested-skill';
    $deepDir = $skillDir.'/references/deep/nested';

    mkdir($deepDir, 0755, true);
    file_put_contents($skillDir.'/SKILL.md', 'test');
    file_put_contents($deepDir.'/file.md', 'nested content');

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $writer = new SkillWriter($agent);
    $result = $writer->remove('nested-skill');

    expect($result)->toBeTrue()
        ->and($skillDir)->not->toBeDirectory();

    cleanupSkillDirectory($absoluteTarget);
});

it('syncs skills by writing new and removing stale', function (): void {
    $sourceDir = fixture('skills/test-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $staleSkillDir = $absoluteTarget.'/stale-skill';
    mkdir($staleSkillDir, 0755, true);
    file_put_contents($staleSkillDir.'/SKILL.md', 'stale content');

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skills = collect([
        'new-skill' => new Skill('new-skill', 'boost', $sourceDir, 'New skill'),
    ]);

    $writer = new SkillWriter($agent);
    $result = $writer->sync($skills, ['stale-skill']);

    expect($result)->toHaveCount(1)
        ->and($result['new-skill'])->toBe(SkillWriter::SUCCESS)
        ->and($absoluteTarget.'/new-skill')->toBeDirectory()
        ->and($staleSkillDir)->not->toBeDirectory();

    cleanupSkillDirectory($absoluteTarget);
});

it('sync preserves skills that exist in both source and target', function (): void {
    $sourceDir = fixture('skills/test-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $existingSkillDir = $absoluteTarget.'/existing-skill';
    mkdir($existingSkillDir, 0755, true);
    file_put_contents($existingSkillDir.'/SKILL.md', 'old content');

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skills = collect([
        'existing-skill' => new Skill('existing-skill', 'boost', $sourceDir, 'Existing skill'),
    ]);

    $writer = new SkillWriter($agent);
    $result = $writer->sync($skills);

    expect($result['existing-skill'])->toBe(SkillWriter::UPDATED)
        ->and($existingSkillDir)->toBeDirectory();

    $content = file_get_contents($existingSkillDir.'/SKILL.md');
    expect($content)->toContain('name: test-skill');

    cleanupSkillDirectory($absoluteTarget);
});

it('sync preserves user-created custom skills that were never tracked', function (): void {
    $sourceDir = fixture('skills/test-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $trackedSkillDir = $absoluteTarget.'/tracked-skill';
    $customSkillDir = $absoluteTarget.'/my-custom-skill';
    mkdir($trackedSkillDir, 0755, true);
    mkdir($customSkillDir, 0755, true);
    file_put_contents($trackedSkillDir.'/SKILL.md', 'tracked content');
    file_put_contents($customSkillDir.'/SKILL.md', 'custom content');

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skills = collect([
        'new-skill' => new Skill('new-skill', 'boost', $sourceDir, 'New skill'),
    ]);

    $writer = new SkillWriter($agent);
    $result = $writer->sync($skills, ['tracked-skill']);

    expect($result['new-skill'])->toBe(SkillWriter::SUCCESS)
        ->and($trackedSkillDir)->not->toBeDirectory()
        ->and($customSkillDir)->toBeDirectory();

    $customContent = file_get_contents($customSkillDir.'/SKILL.md');
    expect($customContent)->toBe('custom content');

    cleanupSkillDirectory($absoluteTarget);
});

it('sync only removes previously tracked skills', function (): void {
    $sourceDir = fixture('skills/test-skill');
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $trackedOneDir = $absoluteTarget.'/tracked-one';
    $trackedTwoDir = $absoluteTarget.'/tracked-two';
    $untrackedDir = $absoluteTarget.'/untracked-skill';
    mkdir($trackedOneDir, 0755, true);
    mkdir($trackedTwoDir, 0755, true);
    mkdir($untrackedDir, 0755, true);
    file_put_contents($trackedOneDir.'/SKILL.md', 'tracked one');
    file_put_contents($trackedTwoDir.'/SKILL.md', 'tracked two');
    file_put_contents($untrackedDir.'/SKILL.md', 'untracked');

    $agent = Mockery::mock(SupportsSkills::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skills = collect([
        'tracked-one' => new Skill('tracked-one', 'boost', $sourceDir, 'Tracked one'),
    ]);

    $writer = new SkillWriter($agent);
    $result = $writer->sync($skills, ['tracked-one', 'tracked-two']);

    expect($result['tracked-one'])->toBe(SkillWriter::UPDATED)
        ->and($trackedOneDir)->toBeDirectory()
        ->and($trackedTwoDir)->not->toBeDirectory()
        ->and($untrackedDir)->toBeDirectory();

    cleanupSkillDirectory($absoluteTarget);
});

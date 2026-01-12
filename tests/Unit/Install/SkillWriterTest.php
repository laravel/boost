<?php

declare(strict_types=1);

use Laravel\Boost\Contracts\SkillsAgent;
use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillWriter;

function cleanupSkillDirectory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }

    rmdir($path);
}

function createTestSkillDir(): array
{
    $tempDir = sys_get_temp_dir().'/boost_skill_source_'.uniqid();
    mkdir($tempDir, 0755, true);
    mkdir($tempDir.'/references', 0755, true);
    file_put_contents($tempDir.'/SKILL.md', "---\nname: test-skill\ndescription: Test skill\n---\n# Test");
    file_put_contents($tempDir.'/references/example.md', '# Example reference');

    return [$tempDir, function () use ($tempDir): void {
        cleanupSkillDirectory($tempDir);
    }];
}

test('it writes skill to target directory', function (): void {
    [$sourceDir, $cleanup] = createTestSkillDir();
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SkillsAgent::class);
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

    $cleanup();
    cleanupSkillDirectory($absoluteTarget);
});

test('it returns UPDATED when skill directory already exists', function (): void {
    [$sourceDir, $cleanup] = createTestSkillDir();
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);
    $targetSkill = $absoluteTarget.'/test-skill';
    mkdir($targetSkill, 0755, true);
    file_put_contents($targetSkill.'/SKILL.md', 'old content');

    $agent = Mockery::mock(SkillsAgent::class);
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

    $cleanup();
    cleanupSkillDirectory($absoluteTarget);
});

test('it returns FAILED when source directory does not exist', function (): void {
    $relativeTarget = '.boost-test-skills-'.uniqid();

    $agent = Mockery::mock(SkillsAgent::class);
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

test('it writes all skills', function (): void {
    [$sourceDir1, $cleanup1] = createTestSkillDir();
    [$sourceDir2, $cleanup2] = createTestSkillDir();
    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SkillsAgent::class);
    $agent->shouldReceive('skillsPath')->andReturn($relativeTarget);

    $skills = collect([
        new Skill('skill-one', 'boost', $sourceDir1, 'First skill'),
        new Skill('skill-two', 'boost', $sourceDir2, 'Second skill'),
    ]);

    $writer = new SkillWriter($agent);
    $results = $writer->writeAll($skills);

    expect($results)->toHaveCount(2)
        ->and($results['skill-one'])->toBe(SkillWriter::SUCCESS)
        ->and($results['skill-two'])->toBe(SkillWriter::SUCCESS);

    $cleanup1();
    $cleanup2();
    cleanupSkillDirectory($absoluteTarget);
});

test('it copies nested directory structure', function (): void {
    $sourceDir = sys_get_temp_dir().'/boost_skill_nested_'.uniqid();
    mkdir($sourceDir.'/references/deep/nested', 0755, true);
    file_put_contents($sourceDir.'/SKILL.md', '# Skill');
    file_put_contents($sourceDir.'/references/ref.md', '# Ref');
    file_put_contents($sourceDir.'/references/deep/nested/file.md', '# Deep');

    $relativeTarget = '.boost-test-skills-'.uniqid();
    $absoluteTarget = base_path($relativeTarget);

    $agent = Mockery::mock(SkillsAgent::class);
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

    cleanupSkillDirectory($sourceDir);
    cleanupSkillDirectory($absoluteTarget);
});

test('it throws exception when target directory cannot be created', function (): void {
    expect(true)->toBeTrue();
})->todo();

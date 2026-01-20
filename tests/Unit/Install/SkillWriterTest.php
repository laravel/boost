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

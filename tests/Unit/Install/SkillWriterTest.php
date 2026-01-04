<?php

declare(strict_types=1);

use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillWriter;

beforeEach(function (): void {
    $this->testDir = sys_get_temp_dir().'/boost-skills-test-'.uniqid();
    mkdir($this->testDir, 0755, true);

    // Set Laravel's base path to our test directory
    $this->app->setBasePath($this->testDir);
});

afterEach(function (): void {
    // Clean up test directory
    if (is_dir($this->testDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->testDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($this->testDir);
    }
});

test('writes skill to correct directory structure', function (): void {
    $writer = new SkillWriter('.skills');
    $skill = new Skill('boost-test', 'Test skill', 'Content here');

    $writer->write($skill);

    $expectedPath = base_path('.skills/boost-test/SKILL.md');
    expect(file_exists($expectedPath))->toBeTrue()
        ->and(file_get_contents($expectedPath))->toContain('name: boost-test');
});

test('writes all skills via writeAll', function (): void {
    $writer = new SkillWriter('.skills');
    $skills = collect([
        new Skill('boost-one', 'First', 'Content one'),
        new Skill('boost-two', 'Second', 'Content two'),
    ]);

    $results = $writer->writeAll($skills);

    expect($results)->toHaveCount(2)
        ->and(file_exists(base_path('.skills/boost-one/SKILL.md')))->toBeTrue()
        ->and(file_exists(base_path('.skills/boost-two/SKILL.md')))->toBeTrue();
});

test('cleans only boost-prefixed skills during cleanup', function (): void {
    $skillsPath = '.skills';
    $basePath = base_path($skillsPath);

    // Create boost and user skills
    mkdir($basePath.'/boost-test', 0755, true);
    mkdir($basePath.'/my-custom-skill', 0755, true);
    file_put_contents($basePath.'/boost-test/SKILL.md', 'boost skill');
    file_put_contents($basePath.'/my-custom-skill/SKILL.md', 'user skill');

    $writer = new SkillWriter($skillsPath);
    $writer->cleanBoostSkills();

    expect(is_dir($basePath.'/boost-test'))->toBeFalse()
        ->and(is_dir($basePath.'/my-custom-skill'))->toBeTrue();
});

test('throws exception when directory creation fails', function (): void {
    // Use an absolute path that bypasses base_path and is non-writable
    $writer = Mockery::mock(SkillWriter::class, ['.skills'])->makePartial();
    $writer->shouldAllowMockingProtectedMethods();
    $writer->shouldReceive('skillPath')
        ->andReturn('/proc/nonexistent/readonly/path');

    $skill = new Skill('boost-test', 'Test', 'Content');

    $writer->write($skill);
})->throws(RuntimeException::class)->skip(PHP_OS_FAMILY === 'Windows', 'Path test not applicable on Windows');

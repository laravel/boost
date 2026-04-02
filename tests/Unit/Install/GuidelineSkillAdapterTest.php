<?php

declare(strict_types=1);

use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\GuidelineSkillAdapter;

beforeEach(function (): void {
    $this->relativeTarget = '.boost-test-guideline-skills-'.uniqid();
    $this->absoluteTarget = base_path($this->relativeTarget);

    $this->agent = Mockery::mock(SupportsSkills::class);
    $this->agent->shouldReceive('skillsPath')->andReturn($this->relativeTarget);

    $this->adapter = new GuidelineSkillAdapter($this->agent);
});

afterEach(function (): void {
    if (! is_dir($this->absoluteTarget)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->absoluteTarget, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
    }

    @rmdir($this->absoluteTarget);
});

test('it writes all guidelines as a single skill', function (): void {
    $result = $this->adapter->sync(collect([
        'foundation' => [
            'content' => '# Foundation Guidelines',
            'name' => 'foundation',
            'description' => 'Foundation Guidelines',
            'path' => '/some/path',
            'custom' => false,
            'third_party' => false,
        ],
        'php' => [
            'content' => '# PHP Guidelines',
            'name' => 'core',
            'description' => 'PHP core guidelines',
            'path' => '/some/path',
            'custom' => false,
            'third_party' => false,
        ],
    ]));

    $skillContent = file_get_contents($this->absoluteTarget.'/laravel-boost-guidelines/SKILL.md');

    expect($result)->toBeTrue()
        ->and($skillContent)->toContain('name: laravel-boost-guidelines')
        ->and($skillContent)->toContain('# Foundation Guidelines')
        ->and($skillContent)->toContain('# PHP Guidelines');
});

test('it returns false when guidelines are empty', function (): void {
    expect($this->adapter->sync(collect()))->toBeFalse();
});

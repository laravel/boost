<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

function createTestSkill(string $path, string $description): void
{
    @mkdir($path, 0755, true);

    $content = <<<SKILL
        ---
        name: livewire-development
        description: {$description}
        ---
        # Test Skill
        SKILL;

    file_put_contents($path.'/SKILL.md', $content);
}

function cleanupTestSkillDirectories(): void
{
    $path = base_path('.ai');

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

function createThirdPartyPackageWithSkill(string $packageName, string $skillName, string $description): string
{
    $vendorPath = base_path("vendor/{$packageName}/resources/boost/skills/{$skillName}");
    @mkdir($vendorPath, 0755, true);

    $skillContent = <<<SKILL
---
name: {$skillName}
description: {$description}
---
# Test Skill
SKILL;

    file_put_contents($vendorPath.'/SKILL.md', $skillContent);

    // Add package to composer.json
    $composerPath = base_path('composer.json');
    $composerData = json_decode((string) file_get_contents($composerPath), true);
    $composerData['require'][$packageName] = '*';
    file_put_contents($composerPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $vendorPath;
}

function cleanupThirdPartyPackage(string $vendorPath): void
{
    @unlink($vendorPath.'/SKILL.md');
    @rmdir($vendorPath);
    @rmdir(dirname($vendorPath));
    @rmdir(dirname($vendorPath, 2));
    @rmdir(dirname($vendorPath, 3));
    @rmdir(dirname($vendorPath, 4));
}

function restoreComposerJson(array $originalRequires): void
{
    $composerPath = base_path('composer.json');
    $composerData = json_decode((string) file_get_contents($composerPath), true);
    $composerData['require'] = $originalRequires;
    file_put_contents($composerPath, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function getComposerRequires(): array
{
    $composerPath = base_path('composer.json');
    $composerData = json_decode((string) file_get_contents($composerPath), true);

    return $composerData['require'] ?? [];
}

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->nodePackageManager = NodePackageManager::NPM;
    $this->roster->shouldReceive('nodePackageManager')->andReturnUsing(
        fn (): NodePackageManager => $this->nodePackageManager
    );

    $this->app->instance(Roster::class, $this->roster);

    $this->config = new GuidelineConfig;
    $this->skillComposer = new SkillComposer($this->roster, $this->config);
});

afterEach(function (): void {
    cleanupTestSkillDirectories();
});

test('skills return collection of Skill objects', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = $this->skillComposer->skills();

    expect($skills)->toBeInstanceOf(Collection::class)
        ->and($skills)->not->toBeEmpty();
});

test('skills discover Boost built-in skills from .ai directory', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = $this->skillComposer->skills();
    $skillNames = $skills->pluck('name')->toArray();

    expect($skillNames)->toContain('livewire-development');
});

test('skill has proper structure with name description and path', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = $this->skillComposer->skills();
    $livewireSkill = $skills->get('livewire-development');

    expect($livewireSkill)->not->toBeNull()
        ->and($livewireSkill->name)->toBe('livewire-development')
        ->and($livewireSkill->description)->not->toBeEmpty()
        ->and($livewireSkill->path)->toBeDirectory()
        ->and($livewireSkill->custom)->toBeFalse();
});

test('skills caches result after the first call', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills1 = $this->skillComposer->skills();
    $skills2 = $this->skillComposer->skills();

    expect($skills1)->toBe($skills2);
});

test('skills ignore directories without SKILL.md or SKILL.blade.php', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = $this->skillComposer->skills();

    expect($skills)->not->toBeEmpty();

    foreach ($skills as $skill) {
        $hasSkillFile = file_exists($skill->path.'/SKILL.md') || file_exists($skill->path.'/SKILL.blade.php');
        expect($hasSkillFile)->toBeTrue();
    }
});

test('skills only includes skills for installed packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = $this->skillComposer->skills();
    $skillNames = $skills->pluck('name')->toArray();

    expect($skillNames)->not->toContain('livewire-development');
});

test('user skills override built-in skills', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    createTestSkill(base_path('.ai/skills/livewire-development'), 'Custom user skill');

    $skills = $this->skillComposer->skills();
    $skill = $skills->get('livewire-development');

    expect($skill->custom)->toBeTrue();
});

test('user package-specific skills override built-in skills', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    createTestSkill(base_path('.ai/livewire/skill/livewire-development'), 'User package override');

    $skills = $this->skillComposer->skills();
    $skill = $skills->get('livewire-development');

    expect($skill->custom)->toBeTrue()
        ->and($skill->description)->toContain('User package override');
});

test('user version-specific skills override package root skills', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    createTestSkill(base_path('.ai/livewire/skill/livewire-development'), 'Root override');
    createTestSkill(base_path('.ai/livewire/3/skill/livewire-development'), 'Version 3 override');

    $skills = $this->skillComposer->skills();
    $skill = $skills->get('livewire-development');

    expect($skill->custom)->toBeTrue()
        ->and($skill->description)->toContain('Version 3 override');
});

test('explicit user skills take the highest priority', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    createTestSkill(base_path('.ai/skills/livewire-development'), 'Explicit override');
    createTestSkill(base_path('.ai/livewire/3/skill/livewire-development'), 'Package override');

    $skills = $this->skillComposer->skills();
    $skill = $skills->get('livewire-development');

    expect($skill->custom)->toBeTrue()
        ->and($skill->description)->toContain('Explicit override');
});

test('user package skills only discovered for installed packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    createTestSkill(base_path('.ai/livewire/skill/livewire-development'), 'Should not be discovered');

    $skills = $this->skillComposer->skills();

    expect($skills->has('livewire-development'))->toBeFalse();
});

test('it excludes all third-party skills when aiGuidelines is empty array', function (): void {
    $originalRequires = getComposerRequires();
    $vendorPath = createThirdPartyPackageWithSkill('test-vendor/test-package', 'test-third-party-skill', 'Test third-party skill');

    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $config = new GuidelineConfig;
    $config->aiGuidelines = [];

    $skillComposer = new SkillComposer($this->roster, $config);
    $skills = $skillComposer->skills();

    cleanupThirdPartyPackage($vendorPath);
    restoreComposerJson($originalRequires);

    expect($skills->has('test-third-party-skill'))->toBeFalse();
});

test('includes all third-party skills when aiGuidelines is not set', function (): void {
    $originalRequires = getComposerRequires();
    $vendorPath = createThirdPartyPackageWithSkill('test-vendor-2/test-package-2', 'test-third-party-skill-2', 'Test third-party skill for backward compatibility');

    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $config = new GuidelineConfig;

    $skillComposer = new SkillComposer($this->roster, $config);
    $skills = $skillComposer->skills();

    cleanupThirdPartyPackage($vendorPath);
    restoreComposerJson($originalRequires);

    expect($skills->has('test-third-party-skill-2'))->toBeTrue();
});

test('includes only third-party skills from selected packages', function (): void {
    $originalRequires = getComposerRequires();
    $vendorPath1 = createThirdPartyPackageWithSkill('selected-vendor/selected-package', 'selected-third-party-skill', 'This skill should be included');
    $vendorPath2 = createThirdPartyPackageWithSkill('unselected-vendor/unselected-package', 'unselected-third-party-skill', 'This skill should be excluded');

    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $config = new GuidelineConfig;
    $config->aiGuidelines = ['selected-vendor/selected-package'];

    $skillComposer = new SkillComposer($this->roster, $config);
    $skills = $skillComposer->skills();

    cleanupThirdPartyPackage($vendorPath1);
    cleanupThirdPartyPackage($vendorPath2);
    restoreComposerJson($originalRequires);

    expect($skills->has('selected-third-party-skill'))->toBeTrue()
        ->and($skills->has('unselected-third-party-skill'))->toBeFalse();
});

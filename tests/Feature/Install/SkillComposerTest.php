<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->roster->shouldReceive('nodePackageManager')->andReturn(NodePackageManager::NPM);
    $this->roster->shouldReceive('usesVersion')->andReturn(false);

    $this->app->instance(Roster::class, $this->roster);
});

test('skills return a collection keyed by skill name', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = (new SkillComposer($this->roster))->skills();

    expect($skills)
        ->toBeInstanceOf(Collection::class)
        ->and($skills->first())->toBeInstanceOf(Skill::class);
});

test('skills are discovered from Boost built-in .ai directory', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = (new SkillComposer($this->roster))->skills();

    expect($skills->has('livewire-development'))->toBeTrue();
});

test('skills only includes skills for installed packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = (new SkillComposer($this->roster))->skills();

    expect($skills->has('livewire-development'))->toBeFalse();
});

test('skill has name, description, path, and package', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skill = (new SkillComposer($this->roster))->skills()->get('livewire-development');

    expect($skill)
        ->name->toBe('livewire-development')
        ->description->not->toBeEmpty()
        ->path->toBeDirectory()
        ->custom->toBeFalse();
});

test('skills result is cached', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $composer = new SkillComposer($this->roster);

    expect($composer->skills())->toBe($composer->skills());
});

test('config change clears skills cache', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $composer = new SkillComposer($this->roster);
    $first = $composer->skills();

    $composer->config(new GuidelineConfig);

    expect($composer->skills())->not->toBe($first);
});

test('excludes livewire skills when indirectly required', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(false),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = (new SkillComposer($this->roster))->skills();

    expect($skills->has('livewire-development'))->toBeFalse();
});

test('excludes skills listed in config exclude list', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    config(['boost.skills.exclude' => ['livewire-development']]);

    $skills = (new SkillComposer($this->roster))->skills();

    expect($skills->has('livewire-development'))->toBeFalse();
});

test('ignores non-existent skill names in exclude list', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    config(['boost.skills.exclude' => ['nonexistent']]);

    $skills = (new SkillComposer($this->roster))->skills();

    expect($skills->has('livewire-development'))->toBeTrue();
});

test('includes livewire skills when directly required', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = (new SkillComposer($this->roster))->skills();

    expect($skills->has('livewire-development'))->toBeTrue();
});

test('blade skills with code before frontmatter are parsed correctly', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skillDir = base_path('.ai/skills/blade-frontmatter-test');
    @mkdir($skillDir, 0755, true);

    file_put_contents($skillDir.'/SKILL.blade.php', <<<'BLADE'
        @php
        $dynamicValue = 'dynamic-description';
        @endphp
        ---
        name: blade-frontmatter-test
        description: This skill has a {{ $dynamicValue }} in the frontmatter
        ---

        # Test Skill

        This skill tests that blade code before frontmatter is processed correctly.
        BLADE);

    try {
        $skills = (new SkillComposer($this->roster))->skills();

        expect($skills->has('blade-frontmatter-test'))->toBeTrue()
            ->and($skills->get('blade-frontmatter-test')->description)
            ->toBe('This skill has a dynamic-description in the frontmatter');
    } finally {
        @unlink($skillDir.'/SKILL.blade.php');
        @rmdir($skillDir);
    }
});

<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

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

test('skills returns collection of Skill objects', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = $this->skillComposer->skills();

    expect($skills)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($skills)->not->toBeEmpty();
});

test('skills discovers Boost built-in skills from .ai directory', function (): void {
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

test('skills caches result after first call', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills1 = $this->skillComposer->skills();
    $skills2 = $this->skillComposer->skills();

    expect($skills1)->toBe($skills2);
});

test('user skills override built-in skills', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $userSkillPath = base_path('.ai/skills/livewire-development');

    if (! is_dir($userSkillPath)) {
        @mkdir($userSkillPath, 0755, true);
        file_put_contents($userSkillPath.'/SKILL.md', "---\nname: livewire-development\ndescription: Custom user skill\n---\n# Custom");

        $skills = $this->skillComposer->skills();
        $skill = $skills->get('livewire-development');

        expect($skill->custom)->toBeTrue();

        unlink($userSkillPath.'/SKILL.md');
        rmdir($userSkillPath);
    }
})->skip(fn (): bool => ! is_writable(base_path('.ai/skills') ?: base_path()));

test('skills ignores directories without SKILL.md or SKILL.blade.php', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = $this->skillComposer->skills();

    foreach ($skills as $skill) {
        $hasSkillFile = file_exists($skill->path.'/SKILL.md') || file_exists($skill->path.'/SKILL.blade.php');
        expect($hasSkillFile)->toBeTrue();
    }
});

test('skills only includes skills for installed packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        // Livewire is NOT included
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = $this->skillComposer->skills();

    $skillNames = $skills->pluck('name')->toArray();

    // Should NOT contain Livewire skill since Livewire is not installed
    expect($skillNames)->not->toContain('livewire-development');
});

test('user package-specific skills override built-in skills', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $userSkillPath = base_path('.ai/livewire/skill/livewire-development');

    if (! is_dir($userSkillPath)) {
        @mkdir($userSkillPath, 0755, true);
        file_put_contents(
            $userSkillPath.'/SKILL.md',
            "---\nname: livewire-development\ndescription: User package override\n---\n# Override"
        );

        $skills = $this->skillComposer->skills();
        $skill = $skills->get('livewire-development');

        expect($skill->custom)->toBeTrue()
            ->and($skill->description)->toContain('User package override');

        unlink($userSkillPath.'/SKILL.md');
        rmdir($userSkillPath);
        @rmdir(base_path('.ai/livewire/skill'));
        @rmdir(base_path('.ai/livewire'));
    }
})->skip(fn (): bool => ! is_writable(base_path('.ai') ?: base_path()));

test('user version-specific skills override package root skills', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $rootSkillPath = base_path('.ai/livewire/skill/livewire-development');
    $versionSkillPath = base_path('.ai/livewire/3/skill/livewire-development');

    @mkdir($rootSkillPath, 0755, true);
    @mkdir($versionSkillPath, 0755, true);

    file_put_contents(
        $rootSkillPath.'/SKILL.md',
        "---\nname: livewire-development\ndescription: Root override\n---\n# Root"
    );
    file_put_contents(
        $versionSkillPath.'/SKILL.md',
        "---\nname: livewire-development\ndescription: Version 3 override\n---\n# V3"
    );

    $skills = $this->skillComposer->skills();
    $skill = $skills->get('livewire-development');

    expect($skill->custom)->toBeTrue()
        ->and($skill->description)->toContain('Version 3 override');

    unlink($versionSkillPath.'/SKILL.md');
    rmdir($versionSkillPath);
    @rmdir(base_path('.ai/livewire/3/skill'));
    @rmdir(base_path('.ai/livewire/3'));
    unlink($rootSkillPath.'/SKILL.md');
    rmdir($rootSkillPath);
    @rmdir(base_path('.ai/livewire/skill'));
    @rmdir(base_path('.ai/livewire'));
})->skip(fn (): bool => ! is_writable(base_path('.ai') ?: base_path()));

test('explicit user skills take highest priority', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $explicitPath = base_path('.ai/skills/livewire-development');
    $packagePath = base_path('.ai/livewire/3/skill/livewire-development');

    @mkdir($explicitPath, 0755, true);
    @mkdir($packagePath, 0755, true);

    file_put_contents(
        $explicitPath.'/SKILL.md',
        "---\nname: livewire-development\ndescription: Explicit override\n---\n# Explicit"
    );
    file_put_contents(
        $packagePath.'/SKILL.md',
        "---\nname: livewire-development\ndescription: Package override\n---\n# Package"
    );

    $skills = $this->skillComposer->skills();
    $skill = $skills->get('livewire-development');

    expect($skill->custom)->toBeTrue()
        ->and($skill->description)->toContain('Explicit override');

    unlink($explicitPath.'/SKILL.md');
    rmdir($explicitPath);
    unlink($packagePath.'/SKILL.md');
    rmdir($packagePath);
    @rmdir(base_path('.ai/livewire/3/skill'));
    @rmdir(base_path('.ai/livewire/3'));
    @rmdir(base_path('.ai/livewire'));
})->skip(fn (): bool => ! is_writable(base_path('.ai') ?: base_path()));

test('user package skills only discovered for installed packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $userSkillPath = base_path('.ai/livewire/skill/livewire-development');

    @mkdir($userSkillPath, 0755, true);
    file_put_contents(
        $userSkillPath.'/SKILL.md',
        "---\nname: livewire-development\ndescription: Should not be discovered\n---\n# No"
    );

    $skills = $this->skillComposer->skills();

    expect($skills->has('livewire-development'))->toBeFalse();

    unlink($userSkillPath.'/SKILL.md');
    rmdir($userSkillPath);
    @rmdir(base_path('.ai/livewire/skill'));
    @rmdir(base_path('.ai/livewire'));
})->skip(fn (): bool => ! is_writable(base_path('.ai') ?: base_path()));

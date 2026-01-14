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

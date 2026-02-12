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

test('includes livewire skills when directly required', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $skills = (new SkillComposer($this->roster))->skills();

    expect($skills->has('livewire-development'))->toBeTrue();
});

test('vendor skills override .ai/ skills with the same name', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $vendorFixture = realpath(\Pest\testDirectory('Fixtures/vendor-skills'));
    expect($vendorFixture)->not->toBeFalse();

    $composer = Mockery::mock(SkillComposer::class, [$this->roster])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('getVendorSkillPath')
        ->andReturnUsing(fn (\Laravel\Roster\Package $package): ?string => $package->rawName() === 'livewire/livewire' ? $vendorFixture : null);

    $skills = $composer->skills();

    expect($skills->has('livewire-development'))->toBeTrue()
        ->and($skills->get('livewire-development')->description)->toBe('Vendor-overridden Livewire skill');
});

test('falls back to .ai/ skills when vendor has none', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $composer = Mockery::mock(SkillComposer::class, [$this->roster])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('getVendorSkillPath')->andReturn(null);

    $skills = $composer->skills();

    expect($skills->has('livewire-development'))->toBeTrue();
});

test('node_modules skills override .ai/ skills for npm first-party packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::INERTIA_REACT, '@inertiajs/react', '2.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $vendorFixture = realpath(\Pest\testDirectory('Fixtures/vendor-skills'));
    expect($vendorFixture)->not->toBeFalse();

    $composer = Mockery::mock(SkillComposer::class, [$this->roster])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('getNodeModulesSkillPath')
        ->andReturnUsing(fn (\Laravel\Roster\Package $package): ?string => $package->rawName() === '@inertiajs/react' ? $vendorFixture : null);

    $skills = $composer->skills();

    $npmSkill = $skills->first(fn ($skill): bool => $skill->description === 'Vendor-overridden Livewire skill');
    expect($npmSkill)->not->toBeNull();
});

test('falls back to .ai/ skills when node_modules has none for npm package', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::INERTIA_REACT, '@inertiajs/react', '2.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $composer = Mockery::mock(SkillComposer::class, [$this->roster])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('getNodeModulesSkillPath')->andReturn(null);

    $skills = $composer->skills();

    expect($skills->has('inertia-react-development'))->toBeTrue();
});

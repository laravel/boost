<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

beforeEach(function (): void {
    $this->project = Mockery::mock(ProjectManager::class);

    $this->app->instance(ProjectManager::class, $this->project);
});

test('skills return a collection keyed by skill name', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    $skills = (new SkillComposer($this->project))->skills();

    expect($skills)
        ->toBeInstanceOf(Collection::class)
        ->and($skills->first())->toBeInstanceOf(Skill::class);
});

test('skills are discovered from Boost built-in .ai directory', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    $skills = (new SkillComposer($this->project))->skills();

    expect($skills->has('livewire-development'))->toBeTrue();
});

test('ships the infer-conventions core skill regardless of installed packages', function (): void {
    mockProjectPackages($this->project, new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]));

    $skills = (new SkillComposer($this->project))->skills();

    $skill = $skills->get('infer-conventions');

    expect($skill)
        ->not->toBeNull()
        ->package->toBe('boost')
        ->description->not->toBeEmpty()
        ->path->toBeDirectory()
        ->and($skill->path.DIRECTORY_SEPARATOR.'references'.DIRECTORY_SEPARATOR.'checklist.blade.php')->toBeFile();
});

test('the infer-conventions core skill can be excluded via config', function (): void {
    mockProjectPackages($this->project, new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]));

    config(['boost.skills.exclude' => ['infer-conventions']]);

    $skills = (new SkillComposer($this->project))->skills();

    expect($skills->has('infer-conventions'))->toBeFalse();
});

test('skills only includes skills for installed packages', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $skills = (new SkillComposer($this->project))->skills();

    expect($skills->has('livewire-development'))->toBeFalse();
});

test('skill has name, description, path, and package', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    $skill = (new SkillComposer($this->project))->skills()->get('livewire-development');

    expect($skill)
        ->name->toBe('livewire-development')
        ->description->not->toBeEmpty()
        ->path->toBeDirectory()
        ->custom->toBeFalse();
});

test('skills result is cached', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = new SkillComposer($this->project);

    expect($composer->skills())->toBe($composer->skills());
});

test('config change clears skills cache', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = new SkillComposer($this->project);
    $first = $composer->skills();

    $composer->config(new GuidelineConfig);

    expect($composer->skills())->not->toBe($first);
});

test('excludes livewire skills when indirectly required', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(false),
    ]);

    mockProjectPackages($this->project, $packages);

    $skills = (new SkillComposer($this->project))->skills();

    expect($skills->has('livewire-development'))->toBeFalse();
});

test('excludes skills listed in config exclude list', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    config(['boost.skills.exclude' => ['livewire-development']]);

    $skills = (new SkillComposer($this->project))->skills();

    expect($skills->has('livewire-development'))->toBeFalse();
});

test('ignores non-existent skill names in exclude list', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    config(['boost.skills.exclude' => ['nonexistent']]);

    $skills = (new SkillComposer($this->project))->skills();

    expect($skills->has('livewire-development'))->toBeTrue();
});

test('includes livewire skills when directly required', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    $skills = (new SkillComposer($this->project))->skills();

    expect($skills->has('livewire-development'))->toBeTrue();
});

test('vendor skills override .ai/ skills with the same name', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    $vendorFixture = realpath(\Pest\testDirectory('Fixtures/vendor-skills'));
    expect($vendorFixture)->not->toBeFalse();

    $composer = Mockery::mock(SkillComposer::class, [$this->project])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->name() === 'livewire/livewire' ? $vendorFixture : null);

    $skills = $composer->skills();

    expect($skills->has('livewire-development'))->toBeTrue()
        ->and($skills->get('livewire-development')->description)->toBe('Vendor-overridden Livewire skill');
});

test('falls back to .ai/ skills when vendor has none', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = Mockery::mock(SkillComposer::class, [$this->project])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')->andReturn(null);

    $skills = $composer->skills();

    expect($skills->has('livewire-development'))->toBeTrue();
});

test('node_modules skills override .ai/ skills for npm first-party packages', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('@inertiajs/react', '2.1.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $vendorFixture = realpath(\Pest\testDirectory('Fixtures/vendor-skills'));
    expect($vendorFixture)->not->toBeFalse();

    $composer = Mockery::mock(SkillComposer::class, [$this->project])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->name() === '@inertiajs/react' ? $vendorFixture : null);

    $skills = $composer->skills();

    $npmSkill = $skills->first(fn ($skill): bool => $skill->description === 'Vendor-overridden Livewire skill');
    expect($npmSkill)->not->toBeNull();
});

test('falls back to .ai/ skills when node_modules has none for npm package', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('@inertiajs/react', '2.1.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = Mockery::mock(SkillComposer::class, [$this->project])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')->andReturn(null);

    $skills = $composer->skills();

    expect($skills->has('inertia-react-development'))->toBeTrue();
});

test('returns all third-party skills when aiGuidelines is uninitialized', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $skillDir = base_path('vendor/some/third-party/resources/boost/skills/third-party-skill');
    @mkdir($skillDir, 0755, true);
    file_put_contents($skillDir.'/SKILL.md', "---\nname: third-party-skill\ndescription: A vendor-provided skill\n---\n\n# Content\n");
    file_put_contents(base_path('composer.json'), json_encode(['require' => ['some/third-party' => '^1.0']]));

    try {
        $skills = (new SkillComposer($this->project))->skills();

        expect($skills->has('third-party-skill'))->toBeTrue();
    } finally {
        @unlink($skillDir.'/SKILL.md');
        @rmdir($skillDir);
        @rmdir(base_path('vendor/some/third-party/resources/boost/skills'));
        @rmdir(base_path('vendor/some/third-party/resources/boost'));
        @rmdir(base_path('vendor/some/third-party/resources'));
        @rmdir(base_path('vendor/some/third-party'));
        @rmdir(base_path('vendor/some'));
        @rmdir(base_path('vendor'));
        @unlink(base_path('composer.json'));
    }
});

test('filters third-party skills to matching packages when aiGuidelines is set', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $skillDir = base_path('vendor/some/third-party/resources/boost/skills/third-party-skill');
    @mkdir($skillDir, 0755, true);
    file_put_contents($skillDir.'/SKILL.md', "---\nname: third-party-skill\ndescription: A vendor-provided skill\n---\n\n# Content\n");
    file_put_contents(base_path('composer.json'), json_encode(['require' => ['some/third-party' => '^1.0']]));

    try {
        $config = new GuidelineConfig;
        $config->aiGuidelines = ['some/third-party'];

        $skills = (new SkillComposer($this->project))->config($config)->skills();

        expect($skills->has('third-party-skill'))->toBeTrue();
    } finally {
        @unlink($skillDir.'/SKILL.md');
        @rmdir($skillDir);
        @rmdir(base_path('vendor/some/third-party/resources/boost/skills'));
        @rmdir(base_path('vendor/some/third-party/resources/boost'));
        @rmdir(base_path('vendor/some/third-party/resources'));
        @rmdir(base_path('vendor/some/third-party'));
        @rmdir(base_path('vendor/some'));
        @rmdir(base_path('vendor'));
        @unlink(base_path('composer.json'));
    }
});

test('excludes third-party skills for packages not in aiGuidelines', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $skillDir = base_path('vendor/some/third-party/resources/boost/skills/third-party-skill');
    @mkdir($skillDir, 0755, true);
    file_put_contents($skillDir.'/SKILL.md', "---\nname: third-party-skill\ndescription: A vendor-provided skill\n---\n\n# Content\n");
    file_put_contents(base_path('composer.json'), json_encode(['require' => ['some/third-party' => '^1.0']]));

    try {
        $config = new GuidelineConfig;
        $config->aiGuidelines = ['other/package'];

        $skills = (new SkillComposer($this->project))->config($config)->skills();

        expect($skills->has('third-party-skill'))->toBeFalse();
    } finally {
        @unlink($skillDir.'/SKILL.md');
        @rmdir($skillDir);
        @rmdir(base_path('vendor/some/third-party/resources/boost/skills'));
        @rmdir(base_path('vendor/some/third-party/resources/boost'));
        @rmdir(base_path('vendor/some/third-party/resources'));
        @rmdir(base_path('vendor/some/third-party'));
        @rmdir(base_path('vendor/some'));
        @rmdir(base_path('vendor'));
        @unlink(base_path('composer.json'));
    }
});

test('blade skills with code before frontmatter are parsed correctly', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

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
        $skills = (new SkillComposer($this->project))->skills();

        expect($skills->has('blade-frontmatter-test'))->toBeTrue()
            ->and($skills->get('blade-frontmatter-test')->description)
            ->toBe('This skill has a dynamic-description in the frontmatter');
    } finally {
        @unlink($skillDir.'/SKILL.blade.php');
        @rmdir($skillDir);
    }
});

test('frontmatter parsing ignores HTML comments injected by third-party packages', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = new SkillComposer($this->project);
    $method = new ReflectionMethod($composer, 'parseSkillFrontmatter');

    $content = <<<'HTML'
        <!-- Start blade view: 'storage/framework/views/bf9245cd.blade.php' -->
        ---
        name: pest-testing
        description: "Write and run tests with Pest"
        ---

        # Content
        HTML;

    $result = $method->invoke($composer, $content);

    expect($result)
        ->toHaveKey('name', 'pest-testing')
        ->toHaveKey('description', 'Write and run tests with Pest');
});

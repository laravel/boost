<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\RuleComposer;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->roster->shouldReceive('nodePackageManager')->andReturn(NodePackageManager::NPM)->byDefault();
    $this->roster->shouldReceive('usesVersion')->andReturn(false)->byDefault();

    $this->app->instance(Roster::class, $this->roster);
});

test('discovers bundled rule files for an installed package', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $rules = (new RuleComposer($this->roster))->rules();

    expect($rules->has('pest/rules/testing'))->toBeTrue();

    $rule = $rules->get('pest/rules/testing');

    expect($rule['paths'])->toBe(['tests/**'])
        ->and($rule['content'])->toContain('Pest')
        ->and($rule['content'])->not->toContain('paths:')
        ->and($rule['content'])->not->toContain('---');
});

test('renders blade rule files with the guideline assist', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $rule = (new RuleComposer($this->roster))->rules()->get('pest/rules/testing');

    expect($rule['content'])->toContain('php artisan test --compact');
});

test('excludes PHPUnit rules when Pest is present due to package priority', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
        new Package(Packages::PHPUNIT, 'phpunit/phpunit', '10.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->roster->shouldReceive('uses')->with(Packages::PEST)->andReturn(true);

    $rules = (new RuleComposer($this->roster))->rules();

    expect($rules->has('pest/rules/testing'))->toBeTrue()
        ->and($rules->has('phpunit/rules/testing'))->toBeFalse();
});

test('loads vendor rules directory overriding the bundled rules', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $vendorFixture = realpath(\Pest\testDirectory('Fixtures/vendor-rules/core-only'));

    $composer = Mockery::mock(RuleComposer::class, [$this->roster])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->rawName() === 'pestphp/pest' && $subpath === 'rules' ? $vendorFixture : null);

    $rule = $composer->rules()->get('pest/rules/testing');

    expect($rule['content'])
        ->toContain('Vendor Rule Guideline')
        ->toContain('loaded from the vendor directory');
});

test('a vendor rule file overrides only its same-named bundled file, not the whole bundled set', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $vendorFixture = realpath(\Pest\testDirectory('Fixtures/vendor-rules/partial-override'));

    $composer = Mockery::mock(RuleComposer::class, [$this->roster])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->rawName() === 'pestphp/pest' && $subpath === 'rules' ? $vendorFixture : null);

    $rules = $composer->rules();

    expect($rules->has('pest/rules/testing'))->toBeTrue()
        ->and($rules->get('pest/rules/testing')['content'])->toContain('Pest')
        ->and($rules->has('pest/rules/extra'))->toBeTrue()
        ->and($rules->get('pest/rules/extra')['content'])->toContain('Vendor Extra Rule');
});

test('skips a rule file with no paths frontmatter', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $vendorFixture = realpath(\Pest\testDirectory('Fixtures/vendor-rules/no-paths'));

    $composer = Mockery::mock(RuleComposer::class, [$this->roster])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->rawName() === 'pestphp/pest' && $subpath === 'rules' ? $vendorFixture : null);

    $rules = $composer->rules();

    // The vendor's orphan.md has no `paths:` frontmatter, so it never produces a rule...
    expect($rules->has('pest/rules/orphan'))->toBeFalse()
        // ...but it only overrides a same-named bundled file, so unrelated bundled files persist.
        ->and($rules->has('pest/rules/testing'))->toBeTrue();
});

test('excludes rules listed in the guidelines exclude config', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    config(['boost.guidelines.exclude' => ['pest/rules/testing']]);

    expect((new RuleComposer($this->roster))->rules()->has('pest/rules/testing'))->toBeFalse();
});

test('composeManaged merges rules that share the exact same paths into one file', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::WAYFINDER, 'laravel/wayfinder', '1.0.0'),
        new Package(Packages::INERTIA_REACT, 'inertiajs/inertia-react', '2.1.0'),
        new Package(Packages::INERTIA_LARAVEL, 'inertiajs/inertia-laravel', '2.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $managed = (new RuleComposer($this->roster))->composeManaged();

    // wayfinder and inertia-react both scope to resources/js/**, so they merge into one file.
    $merged = $managed->first(fn (array $file): bool => $file['paths'] === ['resources/js/**']);

    expect($merged)->not->toBeNull()
        ->and($merged['content'])
        ->toContain('Laravel Wayfinder')
        ->toContain('Inertia + React');
});

test('composeManaged groups rules with different paths into separate files', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $managed = (new RuleComposer($this->roster))->composeManaged();

    $livewireFile = $managed->first(fn (array $file): bool => in_array('app/Livewire/**', $file['paths'], true));
    $testsFile = $managed->first(fn (array $file): bool => $file['paths'] === ['tests/**']);

    expect($managed->count())->toBeGreaterThan(1)
        ->and($livewireFile)->not->toBeNull()
        ->and($testsFile)->not->toBeNull()
        ->and($livewireFile)->not->toBe($testsFile)
        ->and($livewireFile['content'])->not->toContain('Pest')
        ->and($testsFile['content'])->not->toContain('Livewire');
});

test('composeInline exposes rules as guideline-shaped entries', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $inline = (new RuleComposer($this->roster))->composeInline();
    $pest = $inline->get('pest/rules/testing');

    expect($pest)->not->toBeNull()
        ->and($pest['content'])->toContain('Pest')
        ->and($pest['path'])->toBeNull()
        ->and($pest['custom'])->toBeFalse()
        ->and($pest['third_party'])->toBeFalse()
        ->and($pest)->toHaveKey('description')
        ->and($pest)->toHaveKey('tokens');
});

test('composeInline marks third-party rules as third_party', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $ruleDir = base_path('vendor/some/third-party/resources/boost/rules');
    @mkdir($ruleDir, 0755, true);
    file_put_contents($ruleDir.'/widgets.md', "---\npaths:\n  - \"app/Widgets/**\"\n---\n# Widgets\n\nThird-party widget rule.\n");
    file_put_contents(base_path('composer.json'), json_encode(['require' => ['some/third-party' => '^1.0']]));

    try {
        $inline = (new RuleComposer($this->roster))->composeInline();

        expect($inline->get('some/third-party/rules/widgets')['third_party'])->toBeTrue();
    } finally {
        @unlink($ruleDir.'/widgets.md');
        @rmdir($ruleDir);
        @rmdir(base_path('vendor/some/third-party/resources/boost'));
        @rmdir(base_path('vendor/some/third-party/resources'));
        @rmdir(base_path('vendor/some/third-party'));
        @rmdir(base_path('vendor/some'));
        @rmdir(base_path('vendor'));
        @unlink(base_path('composer.json'));
    }
});

test('does not truncate the package name at a "rules" segment when filtering third-party rules', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $ruleDir = base_path('vendor/acme/rules/resources/boost/rules');
    @mkdir($ruleDir, 0755, true);
    file_put_contents($ruleDir.'/widgets.md', "---\npaths:\n  - \"app/Widgets/**\"\n---\n# Widgets\n\nThird-party widget rule.\n");
    file_put_contents(base_path('composer.json'), json_encode(['require' => ['acme/rules' => '^1.0']]));

    try {
        $config = new GuidelineConfig;
        $config->aiGuidelines = ['acme/rules'];

        $rules = (new RuleComposer($this->roster))->config($config)->rules();

        expect($rules->has('acme/rules/rules/widgets'))->toBeTrue();
    } finally {
        @unlink($ruleDir.'/widgets.md');
        @rmdir($ruleDir);
        @rmdir(base_path('vendor/acme/rules/resources/boost'));
        @rmdir(base_path('vendor/acme/rules/resources'));
        @rmdir(base_path('vendor/acme/rules'));
        @rmdir(base_path('vendor/acme'));
        @rmdir(base_path('vendor'));
        @unlink(base_path('composer.json'));
    }
});

test('filters third-party rules to matching packages when aiGuidelines is set', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $ruleDir = base_path('vendor/some/third-party/resources/boost/rules');
    @mkdir($ruleDir, 0755, true);
    file_put_contents($ruleDir.'/widgets.md', "---\npaths:\n  - \"app/Widgets/**\"\n---\n# Widgets\n\nThird-party widget rule.\n");
    file_put_contents(base_path('composer.json'), json_encode(['require' => ['some/third-party' => '^1.0']]));

    try {
        $config = new GuidelineConfig;
        $config->aiGuidelines = ['some/third-party'];

        $rules = (new RuleComposer($this->roster))->config($config)->rules();

        expect($rules->has('some/third-party/rules/widgets'))->toBeTrue()
            ->and($rules->get('some/third-party/rules/widgets')['paths'])->toBe(['app/Widgets/**']);
    } finally {
        @unlink($ruleDir.'/widgets.md');
        @rmdir($ruleDir);
        @rmdir(base_path('vendor/some/third-party/resources/boost'));
        @rmdir(base_path('vendor/some/third-party/resources'));
        @rmdir(base_path('vendor/some/third-party'));
        @rmdir(base_path('vendor/some'));
        @rmdir(base_path('vendor'));
        @unlink(base_path('composer.json'));
    }
});

test('excludes third-party rules for packages not in aiGuidelines', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $ruleDir = base_path('vendor/some/third-party/resources/boost/rules');
    @mkdir($ruleDir, 0755, true);
    file_put_contents($ruleDir.'/widgets.md', "---\npaths:\n  - \"app/Widgets/**\"\n---\n# Widgets\n\nThird-party widget rule.\n");
    file_put_contents(base_path('composer.json'), json_encode(['require' => ['some/third-party' => '^1.0']]));

    try {
        $config = new GuidelineConfig;
        $config->aiGuidelines = ['other/package'];

        $rules = (new RuleComposer($this->roster))->config($config)->rules();

        expect($rules->has('some/third-party/rules/widgets'))->toBeFalse();
    } finally {
        @unlink($ruleDir.'/widgets.md');
        @rmdir($ruleDir);
        @rmdir(base_path('vendor/some/third-party/resources/boost'));
        @rmdir(base_path('vendor/some/third-party/resources'));
        @rmdir(base_path('vendor/some/third-party'));
        @rmdir(base_path('vendor/some'));
        @rmdir(base_path('vendor'));
        @unlink(base_path('composer.json'));
    }
});

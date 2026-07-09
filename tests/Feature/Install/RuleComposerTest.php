<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Install\RuleComposer;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

use function Pest\testDirectory;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->roster->shouldReceive('nodePackageManager')->andReturn(NodePackageManager::NPM)->byDefault();
    $this->roster->shouldReceive('usesVersion')->andReturn(false)->byDefault();

    $this->herd = Mockery::mock(Herd::class);
    $this->herd->shouldReceive('isInstalled')->andReturn(false)->byDefault();

    $this->app->instance(Roster::class, $this->roster);

    $this->guidelines = new GuidelineComposer($this->roster, $this->herd);
});

test('discovers a scoped block for an installed package', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $rules = (new RuleComposer($this->guidelines))->rules();

    $pestRule = $rules->first(fn (array $rule, string $key): bool => str_starts_with($key, 'pest/core#'));

    expect($pestRule)->not->toBeNull()
        ->and($pestRule['paths'])->toBe(['tests/**'])
        ->and($pestRule['content'])->toContain('Pest')
        ->and($pestRule['content'])->not->toContain('@scoped')
        ->and($pestRule['content'])->not->toContain('@endscoped');
});

test('renders blade expressions inside a scoped block', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $rules = (new RuleComposer($this->guidelines))->rules();
    $pestRule = $rules->first(fn (array $rule, string $key): bool => str_starts_with($key, 'pest/core#'));

    expect($pestRule['content'])->toContain('php artisan test --compact');
});

test('a guideline with multiple scoped blocks produces one rule entry per block', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $rules = (new RuleComposer($this->guidelines))->rules();
    $laravelBlocks = $rules->filter(fn (array $rule, string $key): bool => str_starts_with($key, 'laravel/core#'));

    expect($laravelBlocks)->toHaveCount(2);

    $modelsBlock = $laravelBlocks->first(fn (array $rule): bool => $rule['paths'] === ['app/Models/**']);
    $testsBlock = $laravelBlocks->first(fn (array $rule): bool => $rule['paths'] === ['tests/**']);

    expect($modelsBlock)->not->toBeNull()
        ->and($modelsBlock['content'])->toContain('Model Creation')
        ->and($modelsBlock['content'])->not->toContain('Faker')
        ->and($testsBlock)->not->toBeNull()
        ->and($testsBlock['content'])->toContain('Faker')
        ->and($testsBlock['content'])->not->toContain('Model Creation');
});

test('excludes rules for a package excluded by priority', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
        new Package(Packages::PHPUNIT, 'phpunit/phpunit', '10.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->roster->shouldReceive('uses')->with(Packages::PEST)->andReturn(true);

    $rules = (new RuleComposer($this->guidelines))->rules();

    expect($rules->contains(fn (array $rule, string $key): bool => str_starts_with($key, 'pest/core#')))->toBeTrue()
        ->and($rules->contains(fn (array $rule, string $key): bool => str_starts_with($key, 'phpunit/core#')))->toBeFalse();
});

test('excludes rules for a guideline listed in boost.guidelines.exclude', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    config(['boost.guidelines.exclude' => ['pest/core']]);

    $rules = (new RuleComposer($this->guidelines))->rules();

    expect($rules->contains(fn (array $rule, string $key): bool => str_starts_with($key, 'pest/core#')))->toBeFalse();
});

test('overriding a guideline via .ai/guidelines also overrides its scoped blocks', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $customDir = testDirectory('Fixtures/.ai/pest-scoped-override-guidelines');
    @mkdir($customDir.'/pest', 0755, true);
    file_put_contents(
        $customDir.'/pest/core.blade.php',
        "@scoped(['tests/Feature/**'])\n# Custom Pest Rules\n\nAlways use this project's own Pest conventions.\n@endscoped\n"
    );

    try {
        $guidelines = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
        $guidelines
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $customDir.'/'.ltrim((string) $path, '/'));

        $rules = (new RuleComposer($guidelines))->rules();
        $pestRule = $rules->first(fn (array $rule, string $key): bool => str_starts_with($key, 'pest/core#'));

        expect($pestRule)->not->toBeNull()
            ->and($pestRule['paths'])->toBe(['tests/Feature/**'])
            ->and($pestRule['content'])->toContain("Always use this project's own Pest conventions")
            ->and($pestRule['content'])->not->toContain('This project uses Pest for testing');
    } finally {
        @unlink($customDir.'/pest/core.blade.php');
        @rmdir($customDir.'/pest');
        @rmdir($customDir);
    }
});

test('a scoped block from a third-party package guideline is marked third_party', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $guidelineDir = base_path('vendor/some/third-party/resources/boost/guidelines');
    @mkdir($guidelineDir, 0755, true);
    file_put_contents(
        $guidelineDir.'/core.md',
        "# Some Third Party\n\n@scoped(['app/Widgets/**'])\n## Widgets\n\nThird-party widget rule.\n@endscoped\n"
    );
    file_put_contents(base_path('composer.json'), json_encode(['require' => ['some/third-party' => '^1.0']]));

    try {
        $rules = (new RuleComposer($this->guidelines))->rules();
        $widgetRule = $rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Widgets/**']);

        expect($widgetRule)->not->toBeNull()
            ->and($widgetRule['third_party'])->toBeTrue()
            ->and($widgetRule['content'])->toContain('Third-party widget rule');
    } finally {
        @unlink($guidelineDir.'/core.md');
        @rmdir($guidelineDir);
        @rmdir(base_path('vendor/some/third-party/resources/boost'));
        @rmdir(base_path('vendor/some/third-party/resources'));
        @rmdir(base_path('vendor/some/third-party'));
        @rmdir(base_path('vendor/some'));
        @rmdir(base_path('vendor'));
        @unlink(base_path('composer.json'));
    }
});

test('composeManaged merges rules that share the exact same paths into one file', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::WAYFINDER, 'laravel/wayfinder', '1.0.0'),
        new Package(Packages::INERTIA_REACT, 'inertiajs/inertia-react', '2.1.0'),
        new Package(Packages::INERTIA_LARAVEL, 'inertiajs/inertia-laravel', '2.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $managed = (new RuleComposer($this->guidelines))->composeManaged();

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

    $managed = (new RuleComposer($this->guidelines))->composeManaged();

    $livewireFile = $managed->first(fn (array $file): bool => in_array('app/Livewire/**', $file['paths'], true));
    $testsFile = $managed->first(fn (array $file): bool => in_array('tests/**', $file['paths'], true) && count($file['paths']) === 1);

    expect($managed->count())->toBeGreaterThan(1)
        ->and($livewireFile)->not->toBeNull()
        ->and($testsFile)->not->toBeNull()
        ->and($livewireFile)->not->toBe($testsFile)
        ->and($livewireFile['content'])->not->toContain('Pest')
        ->and($testsFile['content'])->not->toContain('Livewire');
});

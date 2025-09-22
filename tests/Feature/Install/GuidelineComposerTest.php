<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Herd;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->herd = Mockery::mock(Herd::class);
    $this->herd->shouldReceive('isInstalled')->andReturn(false)->byDefault();

    // Bind the mock to the service container so it's used everywhere
    $this->app->instance(Roster::class, $this->roster);

    $this->composer = new GuidelineComposer($this->roster, $this->herd);
});

test('includes Inertia React conditional guidelines based on version', function (string $version, bool $shouldIncludeForm, bool $shouldInclude212Features): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::INERTIA_REACT, 'inertiajs/inertia-react', $version),
        new Package(Packages::INERTIA_LARAVEL, 'inertiajs/inertia-laravel', $shouldInclude212Features ? '2.1.2' : '2.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    // Mock all Inertia package version checks
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_LARAVEL, '2.1.0', '>=')
        ->andReturn($shouldIncludeForm);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_REACT, '2.1.0', '>=')
        ->andReturn($shouldIncludeForm);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_SVELTE, '2.1.0', '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_VUE, '2.1.0', '>=')
        ->andReturn(false);

    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_LARAVEL, '2.1.2', '>=')
        ->andReturn($shouldInclude212Features);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_REACT, '2.1.2', '>=')
        ->andReturn($shouldInclude212Features);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_SVELTE, '2.1.2', '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_VUE, '2.1.2', '>=')
        ->andReturn(false);

    $guidelines = $this->composer->compose();

    // Use test markers to verify conditional logic without depending on actual content
    if ($shouldIncludeForm) {
        expect($guidelines)
            ->toContain('`<Form>` Component Example');

        if ($shouldInclude212Features) {
            expect($guidelines)
                ->toContain('form component resetting')
                ->not->toContain('does not support');
        } else {
            expect($guidelines)
                ->toContain('does not support')
                ->not->toContain('form component resetting');
        }
    } else {
        expect($guidelines)
            ->toContain('`useForm` helper')
            ->not->toContain('Example form using the `<Form>` component');
    }
})->with([
    'version 2.0.9 (no features)' => ['2.0.9', false, false],
    'version 2.1.0 (Form component only)' => ['2.1.0', true, false],
    'version 2.1.2 (all features)' => ['2.1.2', true, true],
    'version 2.2.0 (all features)' => ['2.2.0', true, true],
]);

test('includes package guidelines only for installed packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== pest/core rules ===')
        ->not->toContain('=== inertia-react/core rules ===');
});

test('excludes conditional guidelines when config is false', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $config = new GuidelineConfig;
    $config->laravelStyle = false;
    $config->hasAnApi = false;
    $config->caresAboutLocalization = false;
    $config->enforceTests = false;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->not->toContain('=== laravel/style rules ===')
        ->not->toContain('=== laravel/api rules ===')
        ->not->toContain('=== laravel/localization rules ===')
        ->not->toContain('=== tests rules ===');
});

test('includes Herd guidelines only when on .test domain and Herd is installed', function (string $appUrl, bool $herdInstalled, bool $shouldInclude): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->herd->shouldReceive('isInstalled')->andReturn($herdInstalled);

    config(['app.url' => $appUrl]);

    $guidelines = $this->composer->compose();

    if ($shouldInclude) {
        expect($guidelines)->toContain('=== herd rules ===');
    } else {
        expect($guidelines)->not->toContain('=== herd rules ===');
    }
})->with([
    '.test domain with Herd' => ['http://myapp.test', true, true],
    '.test domain without Herd' => ['http://myapp.test', false, false],
    'production domain with Herd' => ['https://myapp.com', true, false],
    'localhost with Herd' => ['http://localhost:8000', true, false],
]);

test('composes guidelines with proper formatting', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toBeString()
        ->toContain('=== foundation rules ===')
        ->toContain('=== boost rules ===')
        ->toContain('=== php rules ===')
        ->toContain('=== laravel/core rules ===')
        ->toContain('=== laravel/v11 rules ===')
        ->toMatch('/=== \w+.*? rules ===/');
});

test('handles multiple package versions correctly', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::INERTIA_REACT, 'inertiajs/inertia-react', '2.1.0'),
        new Package(Packages::INERTIA_VUE, 'inertiajs/inertia-vue', '2.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    // Mock all Inertia package version checks for this test too
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_LARAVEL, '2.1.0', '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_REACT, '2.1.0', '>=')
        ->andReturn(true);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_SVELTE, '2.1.0', '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_VUE, '2.1.0', '>=')
        ->andReturn(false);

    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA, '2.1.2', '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_REACT, '2.1.2', '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_SVELTE, '2.1.2', '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_VUE, '2.1.2', '>=')
        ->andReturn(false);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== inertia-react/core rules ===')
        ->toContain('=== inertia-react/v2/forms rules ===')
        ->toContain('=== inertia-vue/core rules ===')
        ->toContain('=== inertia-vue/v2/forms rules ===')
        ->toContain('=== pest/core rules ===');
});

test('filters out empty guidelines', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->not->toContain('===  rules ===')
        ->not->toMatch('/=== \w+.*? rules ===\s*===/');
});

test('returns list of used guidelines', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.1', true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $config = new GuidelineConfig;
    $config->laravelStyle = true;
    $config->hasAnApi = true;

    $this->composer->config($config);

    $used = $this->composer->used();

    expect($used)
        ->toBeArray()
        ->toContain('foundation')
        ->toContain('boost')
        ->toContain('php')
        ->toContain('laravel/core')
        ->toContain('laravel/v11')
        ->toContain('pest/core');
});

test('includes user custom guidelines from .ai/guidelines directory', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
    $composer
        ->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => realpath(\Pest\testDirectory('fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));

    expect($composer->compose())
        ->toContain('=== .ai/custom-rule rules ===')
        ->toContain('=== .ai/project-specific rules ===')
        ->toContain('This is a custom project-specific guideline')
        ->toContain('Project-specific coding standards')
        ->toContain('Database tables must use `snake_case` naming')
        ->and($composer->used())
        ->toContain('.ai/custom-rule')
        ->toContain('.ai/project-specific');
});

test('non-empty custom guidelines override Boost guidelines', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
    $composer
        ->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => realpath(\Pest\testDirectory('fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));

    $guidelines = $composer->compose();
    $overrideStringCount = substr_count((string) $guidelines, 'Thanks though, appreciate you');

    expect($overrideStringCount)->toBe(1)
        ->and($guidelines)
        ->toContain('Thanks though, appreciate you') // From user guidelines
        ->not->toContain('## Laravel 11') // Heading from Boost's L11/core guideline
        ->and($composer->used())
        ->toContain('.ai/custom-rule')
        ->toContain('.ai/project-specific');
});

test('excludes PHPUnit guidelines when Pest is present due to package priority', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
        new Package(Packages::PHPUNIT, 'phpunit/phpunit', '10.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->roster->shouldReceive('uses')->with(Packages::PEST)->andReturn(true);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== pest/core rules ===')
        ->not->toContain('=== phpunit/core rules ===');
});

test('includes PHPUnit guidelines when Pest is not present', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PHPUNIT, 'phpunit/phpunit', '10.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->roster->shouldReceive('uses')->with(Packages::PEST)->andReturn(false);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== phpunit/core rules ===')
        ->not->toContain('=== pest/core rules ===');
});

<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Support\Composer;
use Laravel\Boost\Support\Npm;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

use function Pest\testDirectory;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->nodePackageManager = NodePackageManager::NPM;
    $this->roster->shouldReceive('nodePackageManager')->andReturnUsing(
        fn (): NodePackageManager => $this->nodePackageManager
    );

    $this->herd = Mockery::mock(Herd::class);
    $this->herd->shouldReceive('isInstalled')->andReturn(false)->byDefault();

    $this->app->instance(Roster::class, $this->roster);

    $this->composer = new GuidelineComposer($this->roster, $this->herd);
});

test('includes Inertia React conditional guidelines based on version', function (string $version): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::INERTIA_REACT, 'inertiajs/inertia-react', $version),
        new Package(Packages::INERTIA_LARAVEL, 'inertiajs/inertia-laravel', $version),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $guidelines = $this->composer->compose();

    // Verify core guidelines reference the skill (detailed examples are in skills now)
    expect($guidelines)
        ->toContain('inertia-react-development');
})->with([
    'version 2.0.9' => ['2.0.9'],
    'version 2.1.0' => ['2.1.0'],
    'version 2.1.2' => ['2.1.2'],
    'version 2.2.0' => ['2.2.0'],
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

test('excludes Herd guidelines when Sail is configured', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->herd->shouldReceive('isInstalled')->andReturn(true);

    config(['app.url' => 'http://myapp.test']);

    $config = new GuidelineConfig;
    $config->usesSail = true;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->not->toContain('Laravel Herd')
        ->toContain('Laravel Sail');

});

test('excludes Sail guidelines when Herd is configured', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->herd->shouldReceive('isInstalled')->andReturn(true);

    config(['app.url' => 'http://myapp.test']);

    $config = new GuidelineConfig;
    $config->usesSail = false;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->toContain('Laravel Herd')
        ->not->toContain('Laravel Sail');
});

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
        ->toContain('=== inertia-vue/core rules ===')
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
        ->andReturnUsing(fn ($path = ''): string => realpath(testDirectory('Fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));

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
        ->andReturnUsing(fn ($path = ''): string => realpath(testDirectory('Fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));

    $guidelines = $composer->compose();
    $overrideStringCount = substr_count((string) $guidelines, 'Thanks though, appreciate you');

    expect($overrideStringCount)->toBe(1)
        ->and($guidelines)
        ->toContain('Thanks though, appreciate you')
        ->not->toContain('## Laravel 11')
        ->toContain('=== laravel/v11 rules ===')
        ->not->toContain('=== .ai/core rules ===')
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

test('excludes laravel/mcp guidelines when indirectly required', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::MCP, 'laravel/mcp', '0.2.2'))->setDirect(false),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->roster->shouldReceive('uses')->with(Packages::LARAVEL)->andReturn(true);
    $this->roster->shouldReceive('uses')->with(Packages::MCP)->andReturn(true);

    expect($this->composer->compose())->not->toContain('Mcp::web');
});

test('includes laravel/mcp guidelines when directly required', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::MCP, 'laravel/mcp', '0.2.2'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->roster->shouldReceive('uses')->with(Packages::LARAVEL)->andReturn(true);
    $this->roster->shouldReceive('uses')->with(Packages::MCP)->andReturn(true);

    expect($this->composer->compose())
        ->toContain('Laravel MCP')
        ->toContain('mcp-development')
        ->not->toContain('Mcp::web');
});

test('excludes livewire guidelines when indirectly required', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(false),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    expect($this->composer->compose())->not->toContain('=== livewire/core rules ===');
});

test('includes livewire guidelines when directly required', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        (new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    expect($this->composer->compose())->toContain('=== livewire/core rules ===');
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

test('includes correct package manager commands in guidelines based on lockfile', function (NodePackageManager $packageManager, string $expectedCommand): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);
    $this->nodePackageManager = $packageManager;
    $this->roster->shouldReceive('packages')->andReturn($packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain("{$expectedCommand} run build")
        ->toContain("{$expectedCommand} run dev");
})->with([
    'npm' => [NodePackageManager::NPM, 'npm'],
    'pnpm' => [NodePackageManager::PNPM, 'pnpm'],
    'yarn' => [NodePackageManager::YARN, 'yarn'],
    'bun' => [NodePackageManager::BUN, 'bun'],
]);

test('renderContent handles blade and markdown files correctly', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::VOLT, 'laravel/volt', '1.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->nodePackageManager = NodePackageManager::NPM;

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
    $composer
        ->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => realpath(testDirectory('Fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));

    $guidelines = $composer->compose();

    expect($guidelines)
        // Preserves backticks in blade templates
        ->toContain('=== .ai/test-blade-with-backticks rules ===')
        ->not->toContain('=== .ai/test-blade-with-backticks.md rules ===')
        ->toContain('`artisan make:model`')
        ->toContain('`php artisan migrate`')
        ->toContain('`Model::query()`')
        ->toContain("`route('home')`")
        ->toContain("`config('app.name')`")
        // Preserves PHP tags in blade templates
        ->toContain('=== .ai/test-blade-with-php-tags rules ===')
        ->not->toContain('=== .ai/test-blade-with-backticks.blade.php rules ===')
        ->toContain('<?php')
        ->toContain('namespace App\Models;')
        ->toContain('class User extends Model')
        // Does not process markdown files with blade
        ->toContain('=== .ai/test-markdown rules ===')
        ->toContain('# Markdown File Test')
        ->toContain('This is a plain markdown file')
        ->toContain('Use `code` in backticks')
        ->toContain('echo "Hello World";')
        // Processes blade variables correctly
        ->toContain('=== .ai/test-blade-with-assist rules ===')
        ->toContain('Run `npm install` to install dependencies')
        ->toContain('Package manager: npm install')
        // Volt guidelines should be included but not skill content
        ->toContain('Livewire Volt')
        ->toContain('volt-development')
        // Skill content should NOT be in guidelines (it's in the skill file)
        ->not->toContain('`@volt`') // This is in the skill, not the guideline
        ->not->toContain('@endvolt')
        ->not->toContain('volt-anonymous-fragment')
        ->not->toContain('@livewire');
});

test('includes wayfinder guidelines with inertia integration when both packages are present', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::WAYFINDER, 'laravel/wayfinder', '1.0.0'),
        new Package(Packages::INERTIA_REACT, 'inertiajs/inertia-react', '2.1.2'),
        new Package(Packages::INERTIA_LARAVEL, 'inertiajs/inertia-laravel', '2.1.2'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_LARAVEL)->andReturn(true);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_REACT)->andReturn(true);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_VUE)->andReturn(false);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_SVELTE)->andReturn(false);

    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_LARAVEL, Mockery::any(), '>=')
        ->andReturn(true);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_REACT, Mockery::any(), '>=')
        ->andReturn(true);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_VUE, Mockery::any(), '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_SVELTE, Mockery::any(), '>=')
        ->andReturn(false);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== wayfinder/core rules ===')
        ->toContain('# Laravel Wayfinder')
        ->toContain('Inertia: Use `.form()` with `<Form>` component');
});

test('includes wayfinder guidelines with inertia vue integration', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::WAYFINDER, 'laravel/wayfinder', '1.0.0'),
        new Package(Packages::INERTIA_VUE, 'inertiajs/inertia-vue', '2.1.2'),
        new Package(Packages::INERTIA_LARAVEL, 'inertiajs/inertia-laravel', '2.1.2'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_LARAVEL)->andReturn(true);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_REACT)->andReturn(false);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_VUE)->andReturn(true);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_SVELTE)->andReturn(false);

    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_LARAVEL, Mockery::any(), '>=')
        ->andReturn(true);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_REACT, Mockery::any(), '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_VUE, Mockery::any(), '>=')
        ->andReturn(true);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_SVELTE, Mockery::any(), '>=')
        ->andReturn(false);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== wayfinder/core rules ===')
        ->toContain('# Laravel Wayfinder')
        ->toContain('Inertia: Use `.form()` with `<Form>` component');
});

test('includes wayfinder guidelines with inertia svelte integration', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::WAYFINDER, 'laravel/wayfinder', '1.0.0'),
        new Package(Packages::INERTIA_SVELTE, 'inertiajs/inertia-svelte', '2.1.2'),
        new Package(Packages::INERTIA_LARAVEL, 'inertiajs/inertia-laravel', '2.1.2'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_LARAVEL)->andReturn(true);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_REACT)->andReturn(false);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_VUE)->andReturn(false);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_SVELTE)->andReturn(true);

    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_LARAVEL, Mockery::any(), '>=')
        ->andReturn(true);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_REACT, Mockery::any(), '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_VUE, Mockery::any(), '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_SVELTE, Mockery::any(), '>=')
        ->andReturn(true);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== wayfinder/core rules ===')
        ->toContain('# Laravel Wayfinder')
        ->toContain('Inertia: Use `.form()` with `<Form>` component');
});

test('includes wayfinder guidelines without inertia integration when inertia is not present', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::WAYFINDER, 'laravel/wayfinder', '1.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_LARAVEL)->andReturn(false);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_REACT)->andReturn(false);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_VUE)->andReturn(false);
    $this->roster->shouldReceive('uses')->with(Packages::INERTIA_SVELTE)->andReturn(false);

    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_LARAVEL, Mockery::any(), '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_REACT, Mockery::any(), '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_VUE, Mockery::any(), '>=')
        ->andReturn(false);
    $this->roster->shouldReceive('usesVersion')
        ->with(Packages::INERTIA_SVELTE, Mockery::any(), '>=')
        ->andReturn(false);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== wayfinder/core rules ===')
        ->toContain('# Laravel Wayfinder')
        ->toContain('Invokable Controllers')
        ->toContain('Parameter Binding')
        ->not->toContain('Inertia:');
});

test('the guidelines are in correct order', function (): void {
    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
    $composer
        ->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => realpath(testDirectory('Fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));

    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);
    $this->roster->shouldReceive('packages')->andReturn($packages);

    $config = new GuidelineConfig;
    $config->enforceTests = true;
    $this->herd->shouldReceive('isInstalled')->andReturn(false);
    $composer->config($config);

    $guidelines = $composer->guidelines();
    $keys = $guidelines->keys()->toArray();

    $firstUserGuidelinePos = collect($keys)->search(fn ($key): bool => str_starts_with((string) $key, '.ai/'));
    $foundationPos = array_search('foundation', $keys, true);
    $testsPos = array_search('tests', $keys, true);
    $pestPos = collect($keys)->search(fn ($key): bool => str_starts_with((string) $key, 'pest/'));

    expect($firstUserGuidelinePos)->not->toBeFalse()
        ->and($foundationPos)->not->toBeFalse()
        ->and($testsPos)->not->toBeFalse()
        ->and($pestPos)->not->toBeFalse()
        ->and($firstUserGuidelinePos)->toBeLessThan($foundationPos)
        ->and($foundationPos)->toBeLessThan($testsPos)
        ->and($testsPos)->toBeLessThan($pestPos);
});

test('excludes FluxUI Free guidelines when FluxUI Pro is present', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::FLUXUI_PRO, 'livewire/flux-pro', '1.0.0'),
        new Package(Packages::FLUXUI_FREE, 'livewire/flux', '1.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->roster->shouldReceive('uses')->with(Packages::FLUXUI_PRO)->andReturn(true);

    $guidelines = $this->composer->guidelines();
    $keys = $guidelines->keys()->toArray();

    $hasFluxPro = collect($keys)->contains(fn ($key): bool => str_contains((string) $key, 'fluxui-pro/'));
    $hasFluxFree = collect($keys)->contains(fn ($key): bool => str_contains((string) $key, 'fluxui-free/'));

    expect($hasFluxPro)->toBeTrue()
        ->and($hasFluxFree)->toBeFalse();
});

test('composeGuidelines filters out empty guidelines', function (): void {
    $guidelines = collect([
        'test/empty' => [
            'content' => '   ',
            'name' => 'empty',
            'path' => '/path/to/empty.md',
            'custom' => false,
        ],
        'test/valid' => [
            'content' => 'Valid content',
            'name' => 'valid',
            'path' => '/path/to/valid.md',
            'custom' => false,
        ],
    ]);

    $composed = GuidelineComposer::composeGuidelines($guidelines);

    expect($composed)
        ->toContain('=== test/valid rules ===')
        ->toContain('Valid content')
        ->not->toContain('=== test/empty rules ===');
});

test('correctly converts package names to hyphens in guideline paths', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::INERTIA_REACT, 'inertiajs/inertia-react', '2.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->roster->shouldReceive('usesVersion')->andReturn(false);

    $guidelines = $this->composer->guidelines();
    $keys = $guidelines->keys()->toArray();

    $hasHyphenated = collect($keys)->contains(fn ($key): bool => str_starts_with((string) $key, 'inertia-react/'));
    $hasUnderscored = collect($keys)->contains(fn ($key): bool => str_starts_with((string) $key, 'inertia_react/'));

    expect($hasHyphenated)->toBeTrue()
        ->and($hasUnderscored)->toBeFalse();
});

test('includes enabled conditional guidelines and orders them before packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->herd->shouldReceive('isInstalled')->andReturn(true);
    config(['app.url' => 'http://myapp.test']);

    $config = new GuidelineConfig;
    $config->enforceTests = true;

    $guidelines = $this->composer->config($config)->guidelines();
    $keys = $guidelines->keys()->toArray();

    expect($keys)
        ->toContain('herd')
        ->toContain('tests');

    $foundationPos = array_search('foundation', $keys, true);
    $testsPos = array_search('tests', $keys, true);
    $pestPos = collect($keys)->search(fn ($key): bool => str_starts_with((string) $key, 'pest/'));

    expect($foundationPos)->not->toBeFalse()
        ->and($testsPos)->not->toBeFalse()
        ->and($pestPos)->not->toBeFalse()
        ->and($testsPos)->toBeGreaterThan($foundationPos)
        ->and($testsPos)->toBeLessThan($pestPos);
});

test('user guidelines are sorted by filename for predictable ordering', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
    $composer
        ->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => realpath(testDirectory('Fixtures/.ai/sorted-guidelines')).'/'.ltrim((string) $path, '/'));

    $guidelines = $composer->guidelines();
    $keys = $guidelines->keys()->toArray();

    // Get the positions of our test guidelines
    $userGuidelineKeys = collect($keys)->filter(fn ($key): bool => str_starts_with((string) $key, '.ai/'))->values()->toArray();

    // Files should be sorted alphabetically by filename:
    // 00-first.md, 10-middle.md, 20-second.md
    expect($userGuidelineKeys)->toBe(['.ai/00-first', '.ai/10-middle', '.ai/20-second']);
});

test('excludes Skills Activation section when skills are disabled', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $config = new GuidelineConfig;
    $config->hasSkills = false;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->not->toContain('## Skills Activation')
        ->not->toContain('This project has domain-specific skills available');
});

test('includes Skills Activation section when skills are enabled and skills exist', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $config = new GuidelineConfig;
    $config->hasSkills = true;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->toContain('## Skills Activation')
        ->toContain('This project has domain-specific skills available');
});

test('loads vendor core guideline when available', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $vendorFixture = realpath(testDirectory('Fixtures/vendor-guidelines/core-only'));

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('getVendorGuidelinePath')
        ->andReturnUsing(fn (\Laravel\Roster\Package $package): ?string => $package->rawName() === 'pestphp/pest' ? $vendorFixture : null);

    $guidelines = $composer->compose();

    expect($guidelines)
        ->toContain('Vendor Core Guideline')
        ->toContain('loaded from the vendor directory');
});

test('falls back to .ai/ when vendor guideline path does not exist', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('getVendorGuidelinePath')->andReturn(null);

    $guidelines = $composer->compose();

    expect($guidelines)->toContain('=== pest/core rules ===');
});

test('guideline key is unchanged regardless of vendor or .ai/ source', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $vendorFixture = realpath(testDirectory('Fixtures/vendor-guidelines/core-only'));

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('getVendorGuidelinePath')
        ->andReturnUsing(fn (\Laravel\Roster\Package $package): ?string => $package->rawName() === 'pestphp/pest' ? $vendorFixture : null);

    $keys = $composer->used();

    expect($keys)->toContain('pest/core');
});

test('user override works with vendor-sourced guideline', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $vendorFixture = realpath(testDirectory('Fixtures/vendor-guidelines/core-only'));

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => realpath(testDirectory('Fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));
    $composer->shouldReceive('getVendorGuidelinePath')
        ->andReturnUsing(fn (\Laravel\Roster\Package $package): ?string => $package->rawName() === 'laravel/framework' ? $vendorFixture : null);

    $guidelines = $composer->guidelines();
    $laravelCore = $guidelines->get('laravel/core');

    expect($laravelCore)->not->toBeNull()
        ->and($laravelCore['content'])->toContain('User Override Laravel Core')
        ->and($laravelCore['content'])->not->toContain('Vendor Core Guideline');
});

test('isFirstPartyPackage identifies known packages', function (): void {
    expect(Composer::isFirstPartyPackage('laravel/framework'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('livewire/livewire'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('pestphp/pest'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('some/third-party'))->toBeFalse();
});

test('isFirstPartyPackage identifies scoped npm packages', function (): void {
    expect(Npm::isFirstPartyPackage('@inertiajs/react'))->toBeTrue()
        ->and(Npm::isFirstPartyPackage('@inertiajs/vue3'))->toBeTrue()
        ->and(Npm::isFirstPartyPackage('@laravel/vite-plugin-wayfinder'))->toBeTrue()
        ->and(Npm::isFirstPartyPackage('some-npm-package'))->toBeFalse()
        ->and(Npm::isFirstPartyPackage('@other/package'))->toBeFalse();
});

test('loads node_modules core guideline for npm first-party packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::INERTIA_REACT, '@inertiajs/react', '2.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $vendorFixture = realpath(testDirectory('Fixtures/vendor-guidelines/core-only'));

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('getNodeModulesGuidelinePath')
        ->andReturnUsing(fn (\Laravel\Roster\Package $package): ?string => $package->rawName() === '@inertiajs/react' ? $vendorFixture : null);

    $guidelines = $composer->compose();

    expect($guidelines)
        ->toContain('Vendor Core Guideline')
        ->toContain('loaded from the vendor directory');
});

test('falls back to .ai/ when node_modules guideline path does not exist for npm package', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::INERTIA_REACT, '@inertiajs/react', '2.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('getNodeModulesGuidelinePath')->andReturn(null);

    $guidelines = $composer->compose();

    expect($guidelines)->toContain('=== inertia-react/core rules ===');
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Support\Composer;
use Laravel\Boost\Support\Npm;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

use function Pest\testDirectory;

beforeEach(function (): void {
    $this->project = Mockery::mock(ProjectManager::class);

    $this->herd = Mockery::mock(Herd::class);
    $this->herd->shouldReceive('isInstalled')->andReturn(false)->byDefault();

    $this->app->instance(ProjectManager::class, $this->project);

    $this->composer = new GuidelineComposer($this->project, $this->herd);
});

test('includes Inertia React conditional guidelines based on version', function (string $version): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('@inertiajs/react', $version),
        rosterPackage('inertiajs/inertia-laravel', $version),
    ]);

    mockProjectPackages($this->project, $packages);

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
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== pest/core rules ===')
        ->not->toContain('=== inertia-react/core rules ===');
});

test('excludes scoped block content from the composed blob when scoped guidelines are enabled', function (): void {
    config(['boost.rules.scoped_guidelines' => true]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
        rosterPackage('livewire/livewire', '3.0.0')->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect(config('boost.rules.scoped_guidelines'))->toBeTrue()
        ->and($guidelines)
        ->not->toContain('=== pest/core rules ===')
        ->not->toContain('=== livewire/core rules ===')
        ->toContain('=== foundation rules ===');
});

test('inlines scoped block content by default since scoped guidelines are opt-in', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect(config('boost.rules.scoped_guidelines'))->toBeFalse()
        ->and($guidelines)
        ->toContain('=== laravel/core rules ===')
        ->toContain('URL Generation')
        ->toContain('Model Creation');
});

test('strips only the scoped portion of a partially-scoped guideline, keeping the rest inline', function (): void {
    config(['boost.rules.scoped_guidelines' => true]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect(config('boost.rules.scoped_guidelines'))->toBeTrue()
        ->and($guidelines)
        ->toContain('=== laravel/core rules ===')
        ->toContain('URL Generation')
        ->not->toContain('APIs & Eloquent Resources')
        ->not->toContain('Model Creation')
        ->not->toContain('When creating new models, create useful factories');
});

test('excludes conditional guidelines when config is false', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

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
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);
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
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);
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
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);
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
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toBeString()
        ->toContain('=== foundation rules ===')
        ->toContain('=== boost rules ===')
        ->toContain('=== php rules ===')
        ->toContain('=== laravel/core rules ===')
        ->toContain('=== deployments rules ===')
        ->toContain('=== laravel/v11 rules ===')
        ->toMatch('/=== \w+.*? rules ===/');
});

test('handles multiple package versions correctly', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('@inertiajs/react', '2.1.0'),
        rosterPackage('@inertiajs/vue3', '2.0.0'),
        rosterPackage('pestphp/pest', '3.1.0'),
    ]);

    mockProjectPackages($this->project, $packages);
    // Mock all Inertia package version checks for this test too

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== inertia-react/core rules ===')
        ->toContain('=== inertia-vue/core rules ===')
        ->toContain('=== pest/core rules ===');
});

test('filters out empty guidelines', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->not->toContain('===  rules ===')
        ->not->toMatch('/=== \w+.*? rules ===\s*===/');
});

test('includes the project rules pointer when rules are enabled and MCP is on', function (): void {
    config()->set('boost.rules.enabled', true);

    mockProjectPackages($this->project, new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]));

    $config = new GuidelineConfig;
    $config->hasMcp = true;

    $guidelines = $this->composer->config($config)->compose();

    expect($guidelines)
        ->toContain('## Project Rules')
        ->toContain('@.ai/rules/index.md')
        ->toContain('record-rule')
        ->toContain('.ai/rules');
});

test('omits the project rules pointer when rules are disabled', function (): void {
    config()->set('boost.rules.enabled', false);

    mockProjectPackages($this->project, new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]));

    $config = new GuidelineConfig;
    $config->hasMcp = true;

    $guidelines = $this->composer->config($config)->compose();

    expect($guidelines)
        ->not->toContain('## Project Rules')
        ->not->toContain('@.ai/rules/index.md');
});

test('returns list of used guidelines', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.1', true),
    ]);

    mockProjectPackages($this->project, $packages);

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
        ->toContain('deployments')
        ->toContain('laravel/v11')
        ->toContain('pest/core');
});

test('includes user custom guidelines from .ai/guidelines directory', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])->makePartial();
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

test('a user override still applies for a package whose bundled core.blade.php no longer exists', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $customDir = testDirectory('Fixtures/.ai/pest-core-override-guidelines');
    @mkdir($customDir.'/pest', 0755, true);
    file_put_contents($customDir.'/pest/core.blade.php', "# Custom Pest Override\n\nAlways use this project's own Pest conventions.\n");

    try {
        $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])->makePartial();
        $composer
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $customDir.'/'.ltrim((string) $path, '/'));

        $guidelines = $composer->guidelines();

        expect($guidelines->get('pest/core')['content'] ?? null)
            ->toContain('Custom Pest Override');
    } finally {
        @unlink($customDir.'/pest/core.blade.php');
        @rmdir($customDir.'/pest');
        @rmdir($customDir);
    }
});

test('non-empty custom guidelines override Boost guidelines', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])->makePartial();
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
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
        rosterPackage('phpunit/phpunit', '10.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== pest/core rules ===')
        ->not->toContain('=== phpunit/core rules ===');
});

test('excludes laravel/mcp guidelines when indirectly required', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('laravel/mcp', '0.2.2'))->setDirect(false),
    ]);

    mockProjectPackages($this->project, $packages);

    expect($this->composer->compose())->not->toContain('Mcp::web');
});

test('excludes livewire guidelines when indirectly required', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(false),
    ]);

    mockProjectPackages($this->project, $packages);

    expect($this->composer->compose())->not->toContain('=== livewire/core rules ===');
});

test('includes livewire guidelines when directly required', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        (rosterPackage('livewire/livewire', '3.0.0'))->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

    expect($this->composer->compose())->toContain('=== livewire/core rules ===');
});

test('includes PHPUnit guidelines when Pest is not present', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('phpunit/phpunit', '10.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== phpunit/core rules ===')
        ->not->toContain('=== pest/core rules ===');
});

test('includes correct package manager commands in guidelines based on lockfile', function (JsPackageManager $packageManager, string $expectedCommand): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);
    mockProjectPackages($this->project, $packages, $packageManager);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain("{$expectedCommand} run build")
        ->toContain("{$expectedCommand} run dev");
})->with([
    'npm' => [JsPackageManager::Npm, 'npm'],
    'pnpm' => [JsPackageManager::Pnpm, 'pnpm'],
    'yarn' => [JsPackageManager::Yarn, 'yarn'],
    'bun' => [JsPackageManager::Bun, 'bun'],
]);

test('renderContent handles blade and markdown files correctly', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('livewire/volt', '1.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);
    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])->makePartial();
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
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('laravel/wayfinder', '1.0.0'),
        rosterPackage('@inertiajs/react', '2.1.2'),
        rosterPackage('inertiajs/inertia-laravel', '2.1.2'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== wayfinder/core rules ===')
        ->toContain('# Laravel Wayfinder')
        ->toContain('Use Wayfinder to generate TypeScript functions for Laravel routes');
});

test('includes wayfinder guidelines with inertia vue integration', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('laravel/wayfinder', '1.0.0'),
        rosterPackage('@inertiajs/vue3', '2.1.2'),
        rosterPackage('inertiajs/inertia-laravel', '2.1.2'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== wayfinder/core rules ===')
        ->toContain('# Laravel Wayfinder')
        ->toContain('Use Wayfinder to generate TypeScript functions for Laravel routes');
});

test('includes wayfinder guidelines with inertia svelte integration', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('laravel/wayfinder', '1.0.0'),
        rosterPackage('@inertiajs/svelte', '2.1.2'),
        rosterPackage('inertiajs/inertia-laravel', '2.1.2'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== wayfinder/core rules ===')
        ->toContain('# Laravel Wayfinder')
        ->toContain('Use Wayfinder to generate TypeScript functions for Laravel routes');
});

test('includes wayfinder guidelines without inertia integration when inertia is not present', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('laravel/wayfinder', '1.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== wayfinder/core rules ===')
        ->toContain('# Laravel Wayfinder')
        ->toContain('Use Wayfinder to generate TypeScript functions for Laravel routes');
});

test('the guidelines are in correct order', function (): void {
    config(['boost.rules.enabled' => false]);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])->makePartial();
    $composer
        ->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => realpath(testDirectory('Fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);
    mockProjectPackages($this->project, $packages);

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
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('@inertiajs/react', '2.1.0'),
    ]);

    mockProjectPackages($this->project, $packages);
    $guidelines = $this->composer->guidelines();
    $keys = $guidelines->keys()->toArray();

    $hasHyphenated = collect($keys)->contains(fn ($key): bool => str_starts_with((string) $key, 'inertia-react/'));
    $hasUnderscored = collect($keys)->contains(fn ($key): bool => str_starts_with((string) $key, 'inertia_react/'));

    expect($hasHyphenated)->toBeTrue()
        ->and($hasUnderscored)->toBeFalse();
});

test('includes enabled conditional guidelines and orders them before packages', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);
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
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])->makePartial();
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

test('excludes boost package from Roster discovery to prevent duplicate core guidelines', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('laravel/boost', '2.1.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = $this->composer->guidelines();
    $keys = $guidelines->keys();

    expect($keys->contains('boost'))->toBeTrue()
        ->and($keys->contains('boost/core'))->toBeFalse();
});

test('excludes Skills Activation section when skills are disabled', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $config = new GuidelineConfig;
    $config->hasSkills = false;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->not->toContain('## Skills Activation')
        ->not->toContain('This project has domain-specific skills available');
});

test('includes Skills Activation section when skills are enabled', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $config = new GuidelineConfig;
    $config->hasSkills = true;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->toContain('## Skills Activation')
        ->toContain('This project has domain-specific skills available');
});

test('excludes guidelines listed in config exclude list', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);
    $this->herd->shouldReceive('isInstalled')->andReturn(true);

    config(['app.url' => 'http://myapp.test']);
    config(['boost.guidelines.exclude' => ['herd', 'tests']]);

    $config = new GuidelineConfig;
    $config->enforceTests = true;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->not->toContain('=== herd rules ===')
        ->not->toContain('=== tests rules ===')
        ->toContain('=== foundation rules ===');
});

test('excludes core guidelines when listed in exclude config', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    config(['boost.guidelines.exclude' => ['php']]);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->not->toContain('=== php rules ===')
        ->toContain('=== foundation rules ===')
        ->toContain('=== boost rules ===');
});

test('excludes package guidelines when listed in exclude config', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    config(['boost.guidelines.exclude' => ['pest/core']]);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->not->toContain('=== pest/core rules ===')
        ->toContain('=== foundation rules ===');
});

test('excludes deployment guidelines when listed in exclude config', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    config(['boost.guidelines.exclude' => ['deployments']]);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->not->toContain('=== deployments rules ===')
        ->toContain('=== laravel/core rules ===')
        ->toContain('=== foundation rules ===');
});

test('excludes versioned package guidelines when listed in exclude config', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '12.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    config(['boost.guidelines.exclude' => ['laravel/v12']]);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->not->toContain('=== laravel/v12 rules ===')
        ->toContain('=== laravel/core rules ===')
        ->toContain('=== foundation rules ===');
});

test('does not exclude user guidelines via config', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])->makePartial();
    $composer
        ->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => realpath(testDirectory('Fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));

    config(['boost.guidelines.exclude' => ['.ai/custom-rule']]);

    expect($composer->compose())
        ->toContain('=== .ai/custom-rule rules ===');
});

test('ignores non-existent keys in guidelines exclude list', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    config(['boost.guidelines.exclude' => ['nonexistent']]);

    $guidelines = $this->composer->compose();

    expect($guidelines)
        ->toContain('=== foundation rules ===')
        ->toContain('=== boost rules ===')
        ->toContain('=== php rules ===');
});

test('excludes guidelines from used() list', function (): void {
    config(['boost.rules.enabled' => false]);

    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    config(['boost.guidelines.exclude' => ['pest/core']]);

    $used = $this->composer->used();

    expect($used)
        ->not->toContain('pest/core')
        ->toContain('foundation');
});

test('excludes MCP Tools and Searching Documentation sections when hasMcp is false', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $config = new GuidelineConfig;
    $config->hasMcp = false;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->not->toContain('## Tools')
        ->not->toContain('database-query')
        ->not->toContain('database-schema')
        ->not->toContain('search-docs')
        ->not->toContain('## Searching Documentation');
});

test('includes MCP Tools and Searching Documentation sections when hasMcp is true', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $config = new GuidelineConfig;
    $config->hasMcp = true;

    $guidelines = $this->composer
        ->config($config)
        ->compose();

    expect($guidelines)
        ->toContain('## Tools')
        ->toContain('database-query')
        ->toContain('database-schema')
        ->toContain('search-docs')
        ->toContain('## Searching Documentation');
});

test('loads vendor core guideline when available', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $vendorFixture = realpath(testDirectory('Fixtures/vendor-guidelines/core-only'));

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->name() === 'pestphp/pest' ? $vendorFixture : null);

    $guidelines = $composer->compose();

    expect($guidelines)
        ->toContain('Vendor Core Guideline')
        ->toContain('loaded from the vendor directory');
});

test('falls back to .ai/ when vendor guideline path does not exist', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')->andReturn(null);

    $guidelines = $composer->compose();

    expect($guidelines)->toContain('=== laravel/core rules ===');
});

test('guideline key is unchanged regardless of vendor or .ai/ source', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $vendorFixture = realpath(testDirectory('Fixtures/vendor-guidelines/core-only'));

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->name() === 'pestphp/pest' ? $vendorFixture : null);

    $keys = $composer->used();

    expect($keys)->toContain('pest/core');
});

test('user override works with vendor-sourced guideline', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $vendorFixture = realpath(testDirectory('Fixtures/vendor-guidelines/core-only'));

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => realpath(testDirectory('Fixtures/.ai/guidelines')).'/'.ltrim((string) $path, '/'));
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->name() === 'laravel/framework' ? $vendorFixture : null);

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
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('@inertiajs/react', '2.1.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $vendorFixture = realpath(testDirectory('Fixtures/vendor-guidelines/core-only'));

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->name() === '@inertiajs/react' ? $vendorFixture : null);

    $guidelines = $composer->compose();

    expect($guidelines)
        ->toContain('Vendor Core Guideline')
        ->toContain('loaded from the vendor directory');
});

test('falls back to .ai/ when node_modules guideline path does not exist for npm package', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('@inertiajs/react', '2.1.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')->andReturn(null);

    $guidelines = $composer->compose();

    expect($guidelines)->toContain('=== laravel/core rules ===');
});

test('user override resolves .md files for vendor-sourced guidelines', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $vendorFixture = realpath(testDirectory('Fixtures/vendor-guidelines/core-only'));

    $mdOverrideDir = testDirectory('Fixtures/.ai/guidelines-md-override');
    @mkdir($mdOverrideDir.'/pest', 0755, true);
    file_put_contents($mdOverrideDir.'/pest/core.md', '# Pest Markdown Override');

    $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $composer->shouldReceive('resolveFirstPartyBoostPath')
        ->andReturnUsing(fn (Package $package, string $subpath): ?string => $package->name() === 'pestphp/pest' ? $vendorFixture : null);
    $composer->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => $mdOverrideDir.'/'.ltrim((string) $path, '/'));

    $guidelines = $composer->guidelines();
    $pestCore = $guidelines->get('pest/core');

    expect($pestCore)->not->toBeNull()
        ->and($pestCore['content'])->toContain('Pest Markdown Override')
        ->and($pestCore['content'])->not->toContain('Vendor Core Guideline');

    @unlink($mdOverrideDir.'/pest/core.md');
    @rmdir($mdOverrideDir.'/pest');
    @rmdir($mdOverrideDir);
});

test('symlinked custom guidelines directory does not produce duplicates', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $realGuidelinesDir = realpath(testDirectory('Fixtures/.ai/guidelines'));
    $symlinkDir = testDirectory('Fixtures/.ai/symlinked-guidelines');

    @unlink($symlinkDir);
    symlink($realGuidelinesDir, $symlinkDir);

    try {
        $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])->makePartial();
        $composer
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $symlinkDir.'/'.ltrim((string) $path, '/'));

        $composed = $composer->compose();
        $overrideCount = substr_count((string) $composed, 'User Override Laravel Core');

        expect($overrideCount)->toBe(1);
    } finally {
        @unlink($symlinkDir);
    }
});

test('symlinked custom guideline file does not produce duplicates', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $customDir = testDirectory('Fixtures/.ai/symlinked-file-guidelines');
    $externalFile = realpath(testDirectory('Fixtures/.ai/guidelines/laravel/core.blade.php'));

    @rmdir($customDir.'/laravel');
    @rmdir($customDir);
    mkdir($customDir.'/laravel', 0755, true);
    symlink($externalFile, $customDir.'/laravel/core.blade.php');

    try {
        $composer = Mockery::mock(GuidelineComposer::class, [$this->project, $this->herd])->makePartial();
        $composer
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $customDir.'/'.ltrim((string) $path, '/'));

        $composed = $composer->compose();
        $overrideCount = substr_count((string) $composed, 'User Override Laravel Core');

        expect($overrideCount)->toBe(1);
    } finally {
        @unlink($customDir.'/laravel/core.blade.php');
        @rmdir($customDir.'/laravel');
        @rmdir($customDir);
    }
});

test('php core guideline adapts enum naming guidance to the application enums', function (array $enums, ?string $fixtureName, string $expected, string $notExpected): void {
    $assist = Mockery::mock(GuidelineAssist::class);
    $assist->shouldReceive('enums')->andReturn($enums);
    $assist->shouldReceive('enumContents')->andReturn($fixtureName === null ? '' : fixtureContent($fixtureName));

    $rendered = Blade::render(file_get_contents(testDirectory('../.ai/php/core.blade.php')), ['assist' => $assist]);

    expect($rendered)
        ->toContain($expected)
        ->not->toContain($notExpected);
})->with([
    'no enums' => [[], null, 'Use TitleCase for Enum keys', 'Follow existing application Enum naming conventions'],
    'TitleCase keys' => [['App\Enums\FlashKey' => 'FlashKey.php'], 'Enums/FlashKey.php', 'Use TitleCase for Enum keys', 'Follow existing application Enum naming conventions'],
    'TitleCase keys with uppercase values' => [['App\Enums\Currency' => 'Currency.php'], 'Enums/Currency.php', 'Use TitleCase for Enum keys', 'Follow existing application Enum naming conventions'],
    'uppercase keys' => [['App\Enums\CountryCode' => 'CountryCode.php'], 'Enums/CountryCode.php', 'Follow existing application Enum naming conventions', 'Use TitleCase for Enum keys'],
]);

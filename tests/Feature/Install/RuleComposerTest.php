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

    expect($laravelBlocks)->toHaveCount(3);

    $modelsBlock = $laravelBlocks->first(fn (array $rule): bool => $rule['paths'] === ['app/Models/**']);
    $apisBlock = $laravelBlocks->first(fn (array $rule): bool => $rule['paths'] === ['app/Http/**', 'routes/**']);
    $testsBlock = $laravelBlocks->first(fn (array $rule): bool => $rule['paths'] === ['tests/**']);

    expect($modelsBlock)->not->toBeNull()
        ->and($modelsBlock['content'])->toContain('Model Creation')
        ->and($modelsBlock['content'])->not->toContain('Faker')
        ->and($apisBlock)->not->toBeNull()
        ->and($apisBlock['content'])->toContain('Eloquent API Resources')
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

test('a scoped block inside a false Blade conditional is not extracted as a rule', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);

    $customDir = testDirectory('Fixtures/.ai/conditional-scoped-guidelines');
    @mkdir($customDir, 0755, true);
    file_put_contents(
        $customDir.'/conditional.blade.php',
        "@if(false)\n@scoped(['app/Never/**'])\n## Never\nHidden branch rule.\n@endscoped\n@endif\n@if(true)\n@scoped(['app/Always/**'])\n## Always\nVisible branch rule.\n@endscoped\n@endif\n"
    );

    try {
        $guidelines = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
        $guidelines
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $customDir.'/'.ltrim((string) $path, '/'));

        $rules = (new RuleComposer($guidelines))->rules();

        expect($rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Never/**']))->toBeNull()
            ->and($rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Always/**']))->not->toBeNull();
    } finally {
        @unlink($customDir.'/conditional.blade.php');
        @rmdir($customDir);
    }
});

test('a headingless scoped block gets a slug-derived title instead of its first line', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $customDir = testDirectory('Fixtures/.ai/headingless-scoped-guidelines');
    @mkdir($customDir, 0755, true);
    file_put_contents(
        $customDir.'/widgets.blade.php',
        "@scoped(['app/Widgets/**'])\n- Always use widget factories.\n- Never delete widgets.\n@endscoped\n"
    );

    try {
        $guidelines = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
        $guidelines
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $customDir.'/'.ltrim((string) $path, '/'));

        $managed = (new RuleComposer($guidelines))->composeManaged();
        $file = $managed->first(fn (array $file): bool => $file['paths'] === ['app/Widgets/**']);

        expect($file)->not->toBeNull()
            ->and($file['title'])->toBe('Widgets')
            ->and($file['content'])->toContain('Always use widget factories');
    } finally {
        @unlink($customDir.'/widgets.blade.php');
        @rmdir($customDir);
    }
});

test('a glob containing a bracket character class survives scoped path parsing', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $customDir = testDirectory('Fixtures/.ai/bracket-glob-guidelines');
    @mkdir($customDir, 0755, true);
    file_put_contents(
        $customDir.'/brackets.blade.php',
        "@scoped(['app/[Ff]oo/**'])\n## Foo\nBracket glob rule.\n@endscoped\n"
    );

    try {
        $guidelines = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
        $guidelines
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $customDir.'/'.ltrim((string) $path, '/'));

        $rules = (new RuleComposer($guidelines))->rules();
        $rule = $rules->first(fn (array $rule): bool => $rule['paths'] === ['app/[Ff]oo/**']);

        expect($rule)->not->toBeNull()
            ->and($rule['content'])->toContain('Bracket glob rule');
    } finally {
        @unlink($customDir.'/brackets.blade.php');
        @rmdir($customDir);
    }
});

test('a scoped block with no parseable paths keeps its content inline instead of losing it', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $customDir = testDirectory('Fixtures/.ai/empty-paths-scoped-guidelines');
    @mkdir($customDir, 0755, true);
    file_put_contents(
        $customDir.'/empty.blade.php',
        "# Empty Paths\n\n@scoped([])\n- Guidance that must not vanish.\n@endscoped\n"
    );

    try {
        $guidelines = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
        $guidelines
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $customDir.'/'.ltrim((string) $path, '/'));

        $rules = (new RuleComposer($guidelines))->rules();
        $inline = $guidelines->guidelines()->get('.ai/empty')['content'] ?? '';

        expect($rules->contains(fn (array $rule): bool => str_contains($rule['content'], 'must not vanish')))->toBeFalse()
            ->and($inline)->toContain('Guidance that must not vanish');
    } finally {
        @unlink($customDir.'/empty.blade.php');
        @rmdir($customDir);
    }
});

test('a glob containing parentheses survives scoped path parsing', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $customDir = testDirectory('Fixtures/.ai/paren-glob-guidelines');
    @mkdir($customDir, 0755, true);
    file_put_contents(
        $customDir.'/parens.blade.php',
        "@scoped(['app/(Foo)/**'])\n## Foo\nParen glob rule.\n@endscoped\n"
    );

    try {
        $guidelines = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
        $guidelines
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $customDir.'/'.ltrim((string) $path, '/'));

        $rules = (new RuleComposer($guidelines))->rules();
        $rule = $rules->first(fn (array $rule): bool => $rule['paths'] === ['app/(Foo)/**']);

        expect($rule)->not->toBeNull()
            ->and($rule['content'])->toContain('Paren glob rule');
    } finally {
        @unlink($customDir.'/parens.blade.php');
        @rmdir($customDir);
    }
});

test('nested scoped blocks never leak sentinels into rule content or inline output', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $customDir = testDirectory('Fixtures/.ai/nested-scoped-guidelines');
    @mkdir($customDir, 0755, true);
    file_put_contents(
        $customDir.'/nested.blade.php',
        "@scoped(['app/Outer/**'])\n## Outer\nOuter rule.\n@scoped(['app/Inner/**'])\n## Inner\nInner rule.\n@endscoped\n@endscoped\n"
    );

    try {
        $guidelines = Mockery::mock(GuidelineComposer::class, [$this->roster, $this->herd])->makePartial();
        $guidelines
            ->shouldReceive('customGuidelinePath')
            ->andReturnUsing(fn ($path = ''): string => $customDir.'/'.ltrim((string) $path, '/'));

        $rules = (new RuleComposer($guidelines))->rules();
        $inline = $guidelines->guidelines()->get('.ai/nested')['content'] ?? '';

        $rules->each(function (array $rule): void {
            expect($rule['content'])->not->toContain('___SCOPED');
        });

        expect($inline)->not->toContain('___SCOPED');
    } finally {
        @unlink($customDir.'/nested.blade.php');
        @rmdir($customDir);
    }
});

test('scopes inertia server-side guidelines to http, routes and js paths', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::INERTIA_LARAVEL, 'inertiajs/inertia-laravel', '2.1.0'),
    ]);

    $this->roster->shouldReceive('packages')->andReturn($packages);
    $this->roster->shouldReceive('uses')->andReturn(false)->byDefault();

    $rules = (new RuleComposer($this->guidelines))->rules();
    $inertiaRule = $rules->first(fn (array $rule, string $key): bool => str_starts_with($key, 'inertia-laravel/core#'));

    expect($inertiaRule)->not->toBeNull()
        ->and($inertiaRule['paths'])->toBe(['app/Http/**', 'routes/**', 'resources/js/**'])
        ->and($inertiaRule['content'])->toContain('Inertia::render()');
});

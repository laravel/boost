<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Install\RuleComposer;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

beforeEach(function (): void {
    $this->project = Mockery::mock(ProjectManager::class);

    $this->herd = Mockery::mock(Herd::class);
    $this->herd->shouldReceive('isInstalled')->andReturn(false)->byDefault();

    $this->app->instance(ProjectManager::class, $this->project);

    $this->guidelines = new GuidelineComposer($this->project, $this->herd);
});

function composerWithFixtureGuidelines(ProjectManager $project, Herd $herd, string $fixture): GuidelineComposer
{
    $dir = fixture($fixture);

    $guidelines = Mockery::mock(GuidelineComposer::class, [$project, $herd])->makePartial();
    $guidelines
        ->shouldReceive('customGuidelinePath')
        ->andReturnUsing(fn ($path = ''): string => $dir.'/'.ltrim((string) $path, '/'));

    return $guidelines;
}

/**
 * Scaffold third-party package guideline files under vendor/, require them in composer.json,
 * run the assertions, then remove everything the scaffold created.
 *
 * @param  array<string, array<string, string>>  $packages  package name => [relative guideline file => contents]
 */
function withThirdPartyPackages(array $packages, Closure $assert): void
{
    $requires = [];

    foreach ($packages as $name => $files) {
        $requires[$name] = '^1.0';
        $guidelineDir = base_path('vendor/'.$name.'/resources/boost/guidelines');

        foreach ($files as $relativePath => $contents) {
            $path = $guidelineDir.'/'.$relativePath;

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $contents);
        }
    }

    File::put(base_path('composer.json'), (string) json_encode(['require' => $requires]));

    try {
        $assert();
    } finally {
        foreach (array_keys($packages) as $name) {
            File::deleteDirectory(base_path('vendor/'.explode('/', $name)[0]));
        }

        @rmdir(base_path('vendor'));
        File::delete(base_path('composer.json'));
    }
}

test('discovers a scoped block for an installed package', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

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
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $rules = (new RuleComposer($this->guidelines))->rules();
    $pestRule = $rules->first(fn (array $rule, string $key): bool => str_starts_with($key, 'pest/core#'));

    expect($pestRule['content'])->toContain('php artisan test --compact');
});

test('a guideline with multiple scoped blocks produces one rule entry per block', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

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
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
        rosterPackage('phpunit/phpunit', '10.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $rules = (new RuleComposer($this->guidelines))->rules();

    expect($rules->contains(fn (array $rule, string $key): bool => str_starts_with($key, 'pest/core#')))->toBeTrue()
        ->and($rules->contains(fn (array $rule, string $key): bool => str_starts_with($key, 'phpunit/core#')))->toBeFalse();
});

test('excludes rules for a guideline listed in boost.guidelines.exclude', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    config(['boost.guidelines.exclude' => ['pest/core']]);

    $rules = (new RuleComposer($this->guidelines))->rules();

    expect($rules->contains(fn (array $rule, string $key): bool => str_starts_with($key, 'pest/core#')))->toBeFalse();
});

test('overriding a guideline via .ai/guidelines also overrides its scoped blocks', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/pest-override');

    $rules = (new RuleComposer($guidelines))->rules();
    $pestRule = $rules->first(fn (array $rule, string $key): bool => str_starts_with($key, 'pest/core#'));

    expect($pestRule)->not->toBeNull()
        ->and($pestRule['paths'])->toBe(['tests/Feature/**'])
        ->and($pestRule['content'])->toContain("Always use this project's own Pest conventions")
        ->and($pestRule['content'])->not->toContain('This project uses Pest for testing');
});

test('a scoped block from a third-party package guideline produces a managed rule file', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    withThirdPartyPackages([
        'some/third-party' => [
            'core.md' => "# Some Third Party\n\n@scoped(['app/Widgets/**'])\n## Widgets\n\nThird-party widget rule.\n@endscoped\n",
        ],
    ], function (): void {
        $managed = (new RuleComposer($this->guidelines))->composeManaged();
        $widgetFile = $managed->first(fn (array $file): bool => $file['paths'] === ['app/Widgets/**']);

        expect($widgetFile)->not->toBeNull()
            ->and($widgetFile['content'])->toContain('Third-party widget rule');
    });
});

test('two scoped guideline files from one third-party package each produce a rule', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    withThirdPartyPackages([
        'some/multi' => [
            'core.md' => "# Multi Core\n\n@scoped(['app/Alpha/**'])\n## Alpha\n\nAlpha rule.\n@endscoped\n",
            'extra.md' => "# Multi Extra\n\n@scoped(['app/Beta/**'])\n## Beta\n\nBeta rule.\n@endscoped\n",
        ],
    ], function (): void {
        $rules = (new RuleComposer($this->guidelines))->rules();
        $alpha = $rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Alpha/**']);
        $beta = $rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Beta/**']);

        expect($alpha)->not->toBeNull()
            ->and($alpha['content'])->toContain('Alpha rule')
            ->and($beta)->not->toBeNull()
            ->and($beta['content'])->toContain('Beta rule');
    });
});

test('composeManaged merges rules that share the exact same paths into one file', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('laravel/wayfinder', '1.0.0'),
        rosterPackage('@inertiajs/react', '2.1.0'),
        rosterPackage('inertiajs/inertia-laravel', '2.1.0'),
    ]);

    mockProjectPackages($this->project, $packages);

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
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
        rosterPackage('livewire/livewire', '3.0.0')->setDirect(true),
    ]);

    mockProjectPackages($this->project, $packages);

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
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/conditional');

    $rules = (new RuleComposer($guidelines))->rules();

    expect($rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Never/**']))->toBeNull()
        ->and($rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Always/**']))->not->toBeNull();
});

test('a headingless scoped block gets a slug-derived title instead of its first line', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/headingless');

    $managed = (new RuleComposer($guidelines))->composeManaged();
    $file = $managed->first(fn (array $file): bool => $file['paths'] === ['app/Widgets/**']);

    expect($file)->not->toBeNull()
        ->and($file['title'])->toBe('Widgets')
        ->and($file['content'])->toContain('Always use widget factories');
});

test('a glob containing a bracket character class survives scoped path parsing', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/bracket-glob');

    $rules = (new RuleComposer($guidelines))->rules();
    $rule = $rules->first(fn (array $rule): bool => $rule['paths'] === ['app/[Ff]oo/**']);

    expect($rule)->not->toBeNull()
        ->and($rule['content'])->toContain('Bracket glob rule');
});

test('a scoped block with no parseable paths keeps its content inline instead of losing it', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/empty-paths');

    $rules = (new RuleComposer($guidelines))->rules();
    $inline = $guidelines->guidelines()->get('.ai/empty')['content'] ?? '';

    expect($rules->contains(fn (array $rule): bool => str_contains($rule['content'], 'must not vanish')))->toBeFalse()
        ->and($inline)->toContain('Guidance that must not vanish');
});

test('a glob containing parentheses survives scoped path parsing', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/paren-glob');

    $rules = (new RuleComposer($guidelines))->rules();
    $rule = $rules->first(fn (array $rule): bool => $rule['paths'] === ['app/(Foo)/**']);

    expect($rule)->not->toBeNull()
        ->and($rule['content'])->toContain('Paren glob rule');
});

test('nested scoped blocks never leak sentinels into rule content or inline output', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/nested');

    $rules = (new RuleComposer($guidelines))->rules();
    $inline = $guidelines->guidelines()->get('.ai/nested')['content'] ?? '';

    $rules->each(function (array $rule): void {
        expect($rule['content'])->not->toContain('___SCOPED');
    });

    expect($inline)->not->toContain('___SCOPED');
});

test('a literal @scoped example inside a fenced code block is not extracted as a rule', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/fenced-literal');

    $rules = (new RuleComposer($guidelines))->rules();
    $inline = $guidelines->guidelines()->get('.ai/fenced')['content'] ?? '';

    expect($rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Example/**']))->toBeNull()
        ->and($inline)->toContain("@scoped(['app/Example/**'])")
        ->and($inline)->toContain('@endscoped');
});

test('a literal @scoped example inside a boostsnippet is not extracted as a rule', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/snippet-literal');

    $rules = (new RuleComposer($guidelines))->rules();
    $inline = $guidelines->guidelines()->get('.ai/snippet')['content'] ?? '';

    expect($rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Snippet/**']))->toBeNull()
        ->and($inline)->toContain("@scoped(['app/Snippet/**'])");
});

test('a literal @scoped example inside a ~~~ fenced block is not extracted as a rule', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/tilde-fenced');

    $rules = (new RuleComposer($guidelines))->rules();
    $inline = $guidelines->guidelines()->get('.ai/tilde')['content'] ?? '';

    expect($rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Example/**']))->toBeNull()
        ->and($inline)->toContain("@scoped(['app/Example/**'])")
        ->and($inline)->toContain('@endscoped');
});

test('re-inlining a scoped block preserves its indentation instead of trimming it', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    config(['boost.rules.enabled' => false]);

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/indented');
    $inline = $guidelines->guidelines()->get('.ai/indented')['content'] ?? '';

    expect($inline)->toContain("\n    Indented body line.");
});

test('nested scoped blocks are left inline instead of being mis-scoped', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $guidelines = composerWithFixtureGuidelines($this->project, $this->herd, 'rules/nested');

    $rules = (new RuleComposer($guidelines))->rules();
    $inline = $guidelines->guidelines()->get('.ai/nested')['content'] ?? '';

    expect($rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Outer/**']))->toBeNull()
        ->and($rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Inner/**']))->toBeNull()
        ->and($inline)->toContain('Outer rule.')
        ->and($inline)->toContain('Inner rule.')
        ->and($inline)->not->toContain('___SCOPED');
});

test('two third-party guideline files sharing a basename in different dirs are both kept', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    withThirdPartyPackages([
        'some/nested' => [
            'admin/core.md' => "# Admin\n\n@scoped(['app/Admin/**'])\n## Admin\n\nAdmin rule.\n@endscoped\n",
            'api/core.md' => "# Api\n\n@scoped(['app/Api/**'])\n## Api\n\nApi rule.\n@endscoped\n",
        ],
    ], function (): void {
        $rules = (new RuleComposer($this->guidelines))->rules();
        $admin = $rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Admin/**']);
        $api = $rules->first(fn (array $rule): bool => $rule['paths'] === ['app/Api/**']);

        expect($admin)->not->toBeNull()
            ->and($admin['content'])->toContain('Admin rule')
            ->and($api)->not->toBeNull()
            ->and($api['content'])->toContain('Api rule');
    });
});

test('a user override in .ai/guidelines overrides a third-party guideline', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    $overrideDir = base_path('.ai/guidelines/some/ovr');
    File::ensureDirectoryExists($overrideDir);
    File::put($overrideDir.'/core.md', "# Overridden\n\nProject-specific third-party guidance.\n");

    try {
        withThirdPartyPackages([
            'some/ovr' => ['core.md' => "# Vendor Default\n\nOriginal third-party guidance.\n"],
        ], function (): void {
            $composer = new GuidelineComposer($this->project, $this->herd);
            $guideline = $composer->resolvedGuidelines()->get('some/ovr/core');

            expect($guideline)->not->toBeNull()
                ->and($guideline['content'])->toContain('Project-specific third-party guidance')
                ->and($guideline['content'])->not->toContain('Original third-party guidance');
        });
    } finally {
        File::deleteDirectory(base_path('.ai/guidelines'));
    }
});

test('third-party package selection matches multi-segment guideline keys', function (): void {
    mockProjectPackages($this->project, new PackageCollection([]));

    withThirdPartyPackages([
        'some/sel' => ['admin/core.md' => "# Admin\n\nAdmin guidance.\n"],
    ], function (): void {
        $selected = new GuidelineConfig;
        $selected->aiGuidelines = ['some/sel'];

        $composer = (new GuidelineComposer($this->project, $this->herd))->config($selected);

        expect($composer->resolvedGuidelines()->keys())->toContain('some/sel/admin/core');

        $other = new GuidelineConfig;
        $other->aiGuidelines = ['some/other'];

        $composer = (new GuidelineComposer($this->project, $this->herd))->config($other);

        expect($composer->resolvedGuidelines()->keys())->not->toContain('some/sel/admin/core');
    });
});

test('scopes inertia server-side guidelines to http, routes and js paths', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('inertiajs/inertia-laravel', '2.1.0'),
    ]);

    mockProjectPackages($this->project, $packages);

    $rules = (new RuleComposer($this->guidelines))->rules();
    $inertiaRule = $rules->first(fn (array $rule, string $key): bool => str_starts_with($key, 'inertia-laravel/core#'));

    expect($inertiaRule)->not->toBeNull()
        ->and($inertiaRule['paths'])->toBe(['app/Http/**', 'routes/**', 'resources/js/**'])
        ->and($inertiaRule['content'])->toContain('Inertia::render()');
});

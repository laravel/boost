<?php

declare(strict_types=1);

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

/**
 * @param  array<int, Package>  $packages
 */
function bootProject(array $packages): ProjectManager
{
    $project = Mockery::mock(ProjectManager::class);
    mockProjectPackages($project, new PackageCollection($packages));
    app()->instance(ProjectManager::class, $project);

    return $project;
}

/**
 * @param  array<int, Package>  $extraPackages
 */
function renderTestingSkill(bool $pest, array $extraPackages = [], string $version = '4.0.0'): string
{
    bootProject(array_merge([
        rosterPackage('laravel/framework', '12.0.0'),
        $pest
            ? rosterPackage('pestphp/pest', $version, true)
            : rosterPackage('phpunit/phpunit', '11.0.0', true),
    ], $extraPackages));

    $renderer = new class
    {
        use RendersBladeGuidelines;

        public function render(string $path): string
        {
            return $this->renderBladeFile($path);
        }
    };

    $skillDir = __DIR__.'/../../../.ai/laravel/skill/testing-best-practices';

    return collect(glob($skillDir.'/rules/*.blade.php') ?: [])
        ->prepend($skillDir.'/SKILL.blade.php')
        ->map(fn (string $path): string => $renderer->render($path))
        ->implode("\n");
}

it('teaches Pest syntax to a Pest project and never PHPUnit syntax', function (): void {
    expect(renderTestingSkill(pest: true))
        ->toContain('This project uses Pest.')
        ->toContain("it('returns 401 when no token is provided'")
        ->toContain('`beforeEach()`')
        ->toContain('https://pestphp.com')
        ->not->toContain('#[DataProvider]')
        ->not->toContain('public function test_')
        ->not->toContain('phpunit.de');
});

it('teaches PHPUnit syntax to a PHPUnit project and never Pest syntax', function (): void {
    expect(renderTestingSkill(pest: false))
        ->toContain('This project uses PHPUnit.')
        ->toContain('public function test_returns_401_when_no_token_is_provided')
        ->toContain('#[DataProvider]')
        ->toContain('setUp()')
        ->toContain('phpunit.de')
        ->not->toContain("it('")
        ->not->toContain('->with(collect(')
        ->not->toContain('beforeEach()')
        ->not->toContain('pestphp.com');
});

it('teaches browser testing when the project installs a browser tool the framework can run', function (bool $pest, string $package, string $docs): void {
    expect(renderTestingSkill($pest, [rosterPackage($package, '1.0.0', true)]))
        ->toContain('tests/Browser')
        ->toContain('## The Browser Tests')
        ->toContain($docs)
        ->not->toContain('neither of which this project installs');
})->with([
    'pest with the plugin' => [true, 'pestphp/pest-plugin-browser', 'https://pestphp.com/docs/browser-testing'],
    'pest with dusk' => [true, 'laravel/dusk', 'https://laravel.com/framework/docs/dusk'],
    'phpunit with dusk' => [false, 'laravel/dusk', 'https://laravel.com/framework/docs/dusk'],
]);

it('names the missing package when the project installs no browser tool the framework can run', function (bool $pest, array $packages, string $missing): void {
    $installed = array_map(fn (string $package): Package => rosterPackage($package, '1.0.0', true), $packages);

    expect(renderTestingSkill($pest, $installed))
        ->toContain('neither of which this project installs')
        ->toContain($missing)
        ->not->toContain('tests/Browser')
        ->not->toContain('## The Browser Tests');
})->with([
    'pest' => [true, [], 'pestphp/pest-plugin-browser'],
    'phpunit' => [false, [], 'laravel/dusk'],
    'phpunit with the pest plugin, which needs pest' => [false, ['pestphp/pest-plugin-browser'], 'laravel/dusk'],
]);

it('puts a convention of the project above its own rules, and never deletes a test without approval', function (bool $pest): void {
    expect(renderTestingSkill($pest))
        ->toContain('project conventions take precedence over this skill')
        ->toContain('Do not delete or rewrite it.')
        ->toContain("Do not delete or rewrite a test without the user's approval");
})->with([
    'pest' => true,
    'phpunit' => false,
]);

it('keeps one case at the endpoint when a unit test owns the matrix', function (bool $pest): void {
    expect(renderTestingSkill($pest))
        ->toContain('### Which Layer Owns Which Case')
        ->toContain('Never remove the last case')
        ->toContain('reduce duplicate higher-level coverage to one case rather than deleting it')
        ->toContain('trim the higher-layer test to one case')
        ->toContain('Testing project configuration is not testing the framework.');
})->with([
    'pest' => true,
    'phpunit' => false,
]);

it('exempts an architecture test from the value rules, which only Pest can write', function (): void {
    expect(renderTestingSkill(pest: true))
        ->toContain('Judge an architecture test by the convention it protects')
        ->toContain('these items do not apply to it');

    expect(renderTestingSkill(pest: false))
        ->not->toContain('architecture test');
});

it('teaches a Pest 5 command only to a project that installs Pest 5', function (): void {
    expect(renderTestingSkill(pest: true, version: '5.0.0'))
        ->toContain('pest --parallel --tia')
        ->toContain('tests/.pest/shards.json')
        ->toContain('The Expectation for a Format');

    expect(renderTestingSkill(pest: true, version: '4.1.0'))
        ->not->toContain('--tia')
        ->not->toContain('shards.json')
        ->not->toContain('The Expectation for a Format');
});

it('ships one testing skill to every project, whichever test framework and major it installs', function (array $package): void {
    $project = bootProject([
        rosterPackage('laravel/framework', '12.0.0'),
        rosterPackage(...$package),
    ]);

    expect((new SkillComposer($project))->skills()->keys())
        ->toContain('testing-best-practices')
        ->not->toContain('pest-testing');
})->with([
    'pest 3' => [['pestphp/pest', '3.8.0', true]],
    'pest 5' => [['pestphp/pest', '5.0.0', true]],
    'pest 6' => [['pestphp/pest', '6.0.0', true]],
    'phpunit' => [['phpunit/phpunit', '11.0.0', true]],
]);

it('resolves every rule file a skill index points at, and references every rule file on disk', function (string $skill, string $extension): void {
    $skillDir = __DIR__.'/../../../.ai/laravel/skill/'.$skill;
    $index = (string) file_get_contents($skillDir.'/SKILL.'.$extension);

    preg_match_all('/\[`(rules\/[a-z-]+)\.md`\]/', $index, $matches);

    $referenced = collect($matches[1])->unique()->sort()->values();
    $onDisk = collect(glob($skillDir.'/rules/*.'.$extension) ?: [])
        ->map(fn (string $path): string => 'rules/'.str_replace('.'.$extension, '', basename($path)))
        ->sort()
        ->values();

    expect($referenced)->not->toBeEmpty()
        ->and($referenced->all())->toBe($onDisk->all());
})->with([
    'testing-best-practices' => ['testing-best-practices', 'blade.php'],
    'laravel-best-practices' => ['laravel-best-practices', 'md'],
]);

it('names a skill that exists when one guideline points a reader at another', function (): void {
    $aiPath = __DIR__.'/../../../.ai';

    $names = collect(glob($aiPath.'/*/skill/*', GLOB_ONLYDIR) ?: [])
        ->merge(glob($aiPath.'/*/*/skill/*', GLOB_ONLYDIR) ?: [])
        ->map(fn (string $path): string => basename($path));

    $pointers = collect(['pest/core.blade.php', 'phpunit/core.blade.php'])
        ->flatMap(function (string $file) use ($aiPath): array {
            preg_match_all('/`([a-z-]+)` skill/', (string) file_get_contents($aiPath.'/'.$file), $matches);

            return $matches[1];
        })
        ->unique();

    expect($pointers)->toContain('testing-best-practices')
        ->and($pointers->diff($names)->all())->toBe([]);
});

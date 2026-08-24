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

function renderTestingSkill(bool $pest, array $extraPackages = []): string
{
    bootProject(array_merge([
        rosterPackage('laravel/framework', '12.0.0'),
        $pest
            ? rosterPackage('pestphp/pest', '4.0.0', true)
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

it('teaches browser testing only when the browser package is installed', function (bool $pest, string $package): void {
    expect(renderTestingSkill($pest, [rosterPackage($package, '1.0.0', true)]))
        ->toContain('tests/Browser')
        ->not->toContain('installs neither of them');

    expect(renderTestingSkill($pest))
        ->toContain('installs neither of them')
        ->toContain($package)
        ->not->toContain('tests/Browser');
})->with([
    'pest' => [true, 'pestphp/pest-plugin-browser'],
    'phpunit' => [false, 'laravel/dusk'],
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

it('ships one testing skill to every Laravel project, whichever test framework it uses', function (bool $pest): void {
    $project = bootProject([
        rosterPackage('laravel/framework', '12.0.0'),
        $pest
            ? rosterPackage('pestphp/pest', '4.0.0', true)
            : rosterPackage('phpunit/phpunit', '11.0.0', true),
    ]);

    expect((new SkillComposer($project))->skills()->keys())
        ->toContain('testing-best-practices')
        ->not->toContain('pest-testing');
})->with([
    'pest' => true,
    'phpunit' => false,
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

it('keeps working on a future Pest major, which the versioned skill directories could not', function (string $version): void {
    $project = bootProject([
        rosterPackage('laravel/framework', '12.0.0'),
        rosterPackage('pestphp/pest', $version, true),
    ]);

    expect((new SkillComposer($project))->skills()->keys())
        ->toContain('testing-best-practices');

    expect(renderTestingSkill(pest: true))
        ->toContain('This project uses Pest.');
})->with([
    'pest 3' => '3.8.0',
    'pest 4' => '4.1.0',
    'pest 5' => '5.0.0',
    'pest 6' => '6.0.0',
]);

it('teaches a Pest 5 command only to a project that installs Pest 5', function (): void {
    $renderForVersion = function (string $version): string {
        bootProject([
            rosterPackage('laravel/framework', '12.0.0'),
            rosterPackage('pestphp/pest', $version, true),
        ]);

        $renderer = new class
        {
            use RendersBladeGuidelines;

            public function render(string $path): string
            {
                return $this->renderBladeFile($path);
            }
        };

        $dir = __DIR__.'/../../../.ai/laravel/skill/testing-best-practices/rules/';

        return $renderer->render($dir.'performance.blade.php').$renderer->render($dir.'assertions.blade.php');
    };

    expect($renderForVersion('5.0.0'))
        ->toContain('pest --parallel --tia')
        ->toContain('tests/.pest/shards.json')
        ->toContain('The Expectation for a Format');

    expect($renderForVersion('4.1.0'))
        ->not->toContain('--tia')
        ->not->toContain('shards.json')
        ->not->toContain('The Expectation for a Format');
});

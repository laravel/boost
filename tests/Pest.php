<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Laravel\Mcp\Response;
use Laravel\Roster\Ecosystems\Ecosystem;
use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Enums\JsPackageManager;
use Laravel\Roster\Enums\PackageSource;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;
use Tests\TestCase;

use function Pest\testDirectory;

uses(TestCase::class)->in('Unit', 'Feature');

expect()->extend('isToolResult', fn () => $this->toBeInstanceOf(Response::class));

expect()->extend('toolTextContains', function (mixed ...$needles): object {
    /** @var Response $this->value */
    $output = (string) $this->value->content();
    expect($output)->toContain(...func_get_args());

    return $this;
});

expect()->extend('toolTextDoesNotContain', function (mixed ...$needles): object {
    /** @var Response $this->value */
    $output = (string) $this->value->content();
    expect($output)->not->toContain(...func_get_args());

    return $this;
});

expect()->extend('toolHasError', function (): object {
    expect($this->value->isError())->toBeTrue();

    return $this;
});

expect()->extend('toolHasNoError', function (): object {
    expect($this->value->isError())->toBeFalse();

    return $this;
});

expect()->extend('toolJsonContent', function (callable $callback): object {
    /** @var Response $this->value */
    $content = json_decode((string) $this->value->content(), true);
    $callback($content);

    return $this;
});

expect()->extend('toolJsonContentToMatchArray', function (array $expectedArray): object {
    /** @var Response $this->value */
    $content = json_decode((string) $this->value->content(), true);
    expect($content)->toMatchArray($expectedArray);

    return $this;
});

if (! function_exists('fixture')) {
    function fixture(string $name): string
    {
        return testDirectory('Fixtures/'.$name);
    }
}

function fixtureContent(string $name): string
{
    return file_get_contents(fixture($name));
}

function rosterPackage(string $name, string $version, bool $dev = false, ?string $path = null): Package
{
    $source = str_starts_with($name, '@') || ! str_contains($name, '/')
        ? PackageSource::Npm
        : PackageSource::Composer;

    return new class($name, $version, $source, $dev, false, '', $path) extends Package
    {
        public function setDirect(bool $direct = true): self
        {
            $this->direct = $direct;

            return $this;
        }
    };
}

function mockProjectPackages(ProjectManager $project, PackageCollection $packages, ?JsPackageManager $packageManager = null): void
{
    $php = new PackageCollection($packages->filter(
        fn (Package $package): bool => $package->source() === PackageSource::Composer,
    )->values()->all());
    $js = new PackageCollection($packages->filter(
        fn (Package $package): bool => $package->source() === PackageSource::Npm,
    )->values()->all());

    $project->shouldReceive('php')->andReturn(new Ecosystem($php));
    $project->shouldReceive('js')->andReturn(new JsEcosystem($js, $packageManager));
}

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Conventions\ConventionInspector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Rules\RuleRepository;

function inferredConvention(): Detection
{
    return new Detection(
        id: 'model-mass-assignment',
        title: 'Guard model mass assignment with $fillable',
        note: "This project's Eloquent models declare mass-assignable attributes with a `\$fillable` allowlist.",
        glob: 'app/Models/**',
        confidence: 1.0,
    );
}

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBase = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-conventions-'.uniqid();

    File::makeDirectory($this->tempBase, 0755, true);
    $this->app->setBasePath($this->tempBase);

    $this->bindInspector = function (array $detectors): void {
        $this->app->instance(ConventionInspector::class, new ConventionInspector($detectors));
    };

    ($this->bindInspector)([stubDetector(inferredConvention())]);

    $this->rulesDir = base_path('.ai/rules');
    $this->app->instance(RuleRepository::class, new RuleRepository($this->rulesDir));
});

afterEach(function (): void {
    File::deleteDirectory($this->tempBase);
    $this->app->setBasePath($this->originalBasePath);
});

it('records detected conventions as path-scoped rules', function (): void {
    $this->artisan('boost:infer-conventions --all')->assertSuccessful();

    expect(File::exists($this->rulesDir.'/index.md'))->toBeTrue();

    $models = File::get($this->rulesDir.'/models.md');
    expect($models)
        ->toContain('app/Models/**')
        ->toContain('## Guard model mass assignment with $fillable');
});

it('does not duplicate rule entries when run twice', function (): void {
    $this->artisan('boost:infer-conventions --all')->assertSuccessful();
    $this->artisan('boost:infer-conventions --all')->assertSuccessful();

    $contents = File::get($this->rulesDir.'/models.md');

    expect(substr_count($contents, '## Guard model mass assignment with $fillable'))->toBe(1);
});

it('does not write opt-in guideline or package rules on --all', function (): void {
    ($this->bindInspector)([
        stubDetector(inferredConvention()),
        stubDetector(new Detection(
            id: 'guideline:eloquent',
            title: 'Eloquent Best Practices',
            note: 'Guideline body.',
            glob: 'app/Models/**',
            confidence: 1.0,
            provenance: Detection::PROVENANCE_BOOST_GUIDELINE,
        )),
    ]);

    $this->artisan('boost:infer-conventions --all')->assertSuccessful();

    $models = File::get($this->rulesDir.'/models.md');

    expect($models)
        ->toContain('## Guard model mass assignment with $fillable')
        ->not->toContain('Eloquent Best Practices');
});

it('reports when no conventions are detected', function (): void {
    ($this->bindInspector)([]);

    $this->artisan('boost:infer-conventions --all')
        ->expectsOutputToContain('No clear conventions detected')
        ->assertSuccessful();
});

it('writes nothing on a dry run', function (): void {
    $this->artisan('boost:infer-conventions --all --dry-run')
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    expect(File::isDirectory($this->rulesDir))->toBeFalse();
});

it('shows the rule content on --diff without writing', function (): void {
    $this->artisan('boost:infer-conventions --all --diff')
        ->expectsOutputToContain('+ ## Guard model mass assignment with $fillable')
        ->assertSuccessful();

    expect(File::isDirectory($this->rulesDir))->toBeFalse();
});

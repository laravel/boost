<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Conventions\Contracts\Detector;
use Laravel\Boost\Install\Conventions\ConventionInspector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\DetectionContext;
use Laravel\Boost\Install\Conventions\Detectors\GuardedFillableDetector;
use Laravel\Boost\Install\Conventions\FileSampler;
use Laravel\Boost\Install\Conventions\SourceRoots;
use Laravel\Boost\Rules\RuleRepository;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBase = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-conventions-'.uniqid();

    File::makeDirectory($this->tempBase, 0755, true);
    File::copyDirectory(fixture('conventions/fillable-models-app'), $this->tempBase);

    $this->app->setBasePath($this->tempBase);

    $this->app->instance(ConventionInspector::class, new ConventionInspector(new SourceRoots, new FileSampler, [
        new GuardedFillableDetector,
    ]));

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
    $guideline = new class implements Detector
    {
        public function id(): string
        {
            return 'guideline:eloquent';
        }

        public function detect(DetectionContext $context): Collection
        {
            return new Collection([new Detection(
                id: 'guideline:eloquent',
                title: 'Eloquent Best Practices',
                note: 'Guideline body.',
                glob: 'app/Models/**',
                confidence: 1.0,
                provenance: Detection::PROVENANCE_BOOST_GUIDELINE,
            )]);
        }
    };

    $this->app->instance(ConventionInspector::class, new ConventionInspector(new SourceRoots, new FileSampler, [
        new GuardedFillableDetector,
        $guideline,
    ]));

    $this->artisan('boost:infer-conventions --all')->assertSuccessful();

    $models = File::get($this->rulesDir.'/models.md');

    expect($models)
        ->toContain('## Guard model mass assignment with $fillable')
        ->not->toContain('Eloquent Best Practices');
});

it('reports when no conventions are detected', function (): void {
    File::cleanDirectory($this->tempBase);

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

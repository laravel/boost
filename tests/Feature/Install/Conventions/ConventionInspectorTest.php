<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Install\Conventions\Contracts\Detector;
use Laravel\Boost\Install\Conventions\ConventionInspector;
use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\DetectionContext;
use Laravel\Boost\Install\Conventions\Detectors\GuardedFillableDetector;
use Laravel\Boost\Install\Conventions\FileSampler;
use Laravel\Boost\Install\Conventions\SourceRoots;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->app->setBasePath(fixture('conventions/fillable-models-app'));

    $this->inspector = new ConventionInspector(new SourceRoots, new FileSampler, [
        new GuardedFillableDetector,
    ]);
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
});

it('detects the mass-assignment convention from the codebase', function (): void {
    $ids = $this->inspector->inspect()->pluck('id');

    expect($ids)->toContain('model-mass-assignment');
});

it('returns detections deduped by id and sorted by descending confidence', function (): void {
    $detections = $this->inspector->inspect();

    expect($detections->pluck('id')->all())->toBe($detections->pluck('id')->unique()->values()->all());

    $confidences = $detections->pluck('confidence')->all();
    $sorted = $confidences;
    rsort($sorted);
    expect($confidences)->toBe($sorted);
});

it('marks high-confidence detections as preselected against the threshold', function (): void {
    $detections = $this->inspector->inspect(0.9);

    $detection = $detections->firstWhere('id', 'model-mass-assignment');
    expect($detection->preselected)->toBeTrue();
});

it('keeps the highest-confidence detection when two detectors emit the same id', function (): void {
    $low = new class implements Detector
    {
        public function id(): string
        {
            return 'shared';
        }

        public function detect(DetectionContext $context): Collection
        {
            return new Collection([new Detection('shared', 'Low', 'low note', 'app/**', 0.40)]);
        }
    };

    $high = new class implements Detector
    {
        public function id(): string
        {
            return 'shared';
        }

        public function detect(DetectionContext $context): Collection
        {
            return new Collection([new Detection('shared', 'High', 'high note', 'app/**', 0.95)]);
        }
    };

    $inspector = new ConventionInspector(new SourceRoots, new FileSampler, [$low, $high]);

    $detection = $inspector->inspect()->firstWhere('id', 'shared');

    expect($detection->confidence)->toBe(0.95);
    expect($detection->title)->toBe('High');
});

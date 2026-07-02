<?php

declare(strict_types=1);

use Laravel\Boost\Install\Conventions\ConventionInspector;
use Laravel\Boost\Install\Conventions\Detection;

it('returns detections deduped by id and sorted by descending confidence', function (): void {
    $inspector = new ConventionInspector([
        stubDetector(new Detection('a', 'A', 'note', 'app/**', 0.40)),
        stubDetector(new Detection('b', 'B', 'note', 'app/**', 0.95)),
    ]);

    $detections = $inspector->inspect();

    expect($detections->pluck('id')->all())->toBe($detections->pluck('id')->unique()->values()->all());

    $confidences = $detections->pluck('confidence')->all();
    $sorted = $confidences;
    rsort($sorted);
    expect($confidences)->toBe($sorted);
});

it('marks high-confidence inferred detections as preselected against the threshold', function (): void {
    $inspector = new ConventionInspector([
        stubDetector(new Detection('a', 'A', 'note', 'app/**', 0.95)),
    ]);

    $detection = $inspector->inspect(0.9)->firstWhere('id', 'a');

    expect($detection->preselected)->toBeTrue();
});

it('never preselects opt-in package or guideline detections', function (): void {
    $inspector = new ConventionInspector([
        stubDetector(new Detection('g', 'G', 'note', 'app/**', 1.0, provenance: Detection::PROVENANCE_BOOST_GUIDELINE)),
    ]);

    $detection = $inspector->inspect(0.5)->firstWhere('id', 'g');

    expect($detection->preselected)->toBeFalse();
});

it('keeps the highest-confidence detection when two detectors emit the same id', function (): void {
    $inspector = new ConventionInspector([
        stubDetector(new Detection('shared', 'Low', 'low note', 'app/**', 0.40)),
        stubDetector(new Detection('shared', 'High', 'high note', 'app/**', 0.95)),
    ]);

    $detection = $inspector->inspect()->firstWhere('id', 'shared');

    expect($detection->confidence)->toBe(0.95);
    expect($detection->title)->toBe('High');
});

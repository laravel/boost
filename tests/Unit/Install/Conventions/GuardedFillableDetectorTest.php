<?php

declare(strict_types=1);

use Laravel\Boost\Install\Conventions\Detectors\GuardedFillableDetector;

it('detects a $fillable convention across the models', function (): void {
    $base = fixture('conventions/fillable-models-app');

    $detections = (new GuardedFillableDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toHaveCount(1);

    $detection = $detections->first();

    expect($detection->id)->toBe('model-mass-assignment');
    expect($detection->glob)->toBe('app/Models/**');
    expect($detection->confidence)->toBe(1.0);
    expect($detection->title)->toContain('$fillable');
    expect($detection->note)->toContain('6/6 models');
});

it('detects a $guarded convention across the models', function (): void {
    $base = fixture('conventions/guarded-models-app');

    $detections = (new GuardedFillableDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toHaveCount(1);
    expect($detections->first()->title)->toContain('$guarded');
    expect($detections->first()->note)->toContain('5/5 models');
});

it('scopes the rule to a non-app source root where the models actually live', function (): void {
    $base = fixture('conventions/modular-models-app');

    $detections = (new GuardedFillableDetector)->detect(conventionContext($base, [$base.'/src/Domain']));

    expect($detections)->toHaveCount(1);
    expect($detections->first()->glob)->toBe('src/Domain/Models/**');
});

it('samples models nested below a source root, not just directly under it', function (): void {
    $base = fixture('conventions/nested-models-app');

    $detections = (new GuardedFillableDetector)->detect(conventionContext($base, [$base.'/src/Domain']));

    expect($detections)->toHaveCount(1);
    expect($detections->first()->glob)->toBe('src/Domain/Orders/Models/**');
    expect($detections->first()->note)->toContain('5/5 models');
});

it('stays silent when there are too few models to be confident', function (): void {
    $base = fixture('conventions/carbon-app');

    $detections = (new GuardedFillableDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toBeEmpty();
});

it('stays silent when models are split between styles (Wilson gate rejects mixed)', function (): void {
    $base = fixture('conventions/mixed-models-app');

    $detections = (new GuardedFillableDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toBeEmpty();
});

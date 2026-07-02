<?php

declare(strict_types=1);

use Laravel\Boost\Install\Conventions\Detectors\ValidationRuleSyntaxDetector;

it('detects pipe-delimited validation rule syntax across form requests', function (): void {
    $base = fixture('conventions/pipe-validation-app');

    $detections = (new ValidationRuleSyntaxDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toHaveCount(1);

    $detection = $detections->first();

    expect($detection->id)->toBe('validation-rule-syntax');
    expect($detection->glob)->toBe('app/Http/Requests/**');
    expect($detection->title)->toContain('pipe syntax');
    expect($detection->note)->toContain('5/5 form requests');
});

it('stays silent when there are no form requests', function (): void {
    $base = fixture('conventions/fillable-models-app');

    $detections = (new ValidationRuleSyntaxDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toBeEmpty();
});

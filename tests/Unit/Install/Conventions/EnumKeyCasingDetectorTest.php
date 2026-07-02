<?php

declare(strict_types=1);

use Laravel\Boost\Install\Conventions\Detectors\EnumKeyCasingDetector;

it('detects SCREAMING_SNAKE_CASE enum case naming', function (): void {
    $base = fixture('conventions/screaming-enums-app');

    $detections = (new EnumKeyCasingDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toHaveCount(1);

    $detection = $detections->first();

    expect($detection->id)->toBe('enum-key-casing');
    expect($detection->title)->toContain('SCREAMING_SNAKE_CASE');
    expect($detection->glob)->toBe('app/Enums/**');
    expect($detection->note)->toContain('5/5 cases');
});

it('stays silent when there are no enums', function (): void {
    $base = fixture('conventions/fillable-models-app');

    $detections = (new EnumKeyCasingDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toBeEmpty();
});

it('counts only enum cases, not switch-statement case labels in the same file', function (): void {
    $base = fixture('conventions/enum-switch-app');

    $detections = (new EnumKeyCasingDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toHaveCount(1);
    expect($detections->first()->title)->toContain('SCREAMING_SNAKE_CASE');
    expect($detections->first()->note)->toContain('5/5 cases');
});

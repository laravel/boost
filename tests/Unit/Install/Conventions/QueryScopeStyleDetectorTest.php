<?php

declare(strict_types=1);

use Laravel\Boost\Install\Conventions\Detectors\QueryScopeStyleDetector;

it('detects the #[Scope] attribute style across models', function (): void {
    $base = fixture('conventions/attribute-scopes-app');

    $detections = (new QueryScopeStyleDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toHaveCount(1);

    $detection = $detections->first();

    expect($detection->id)->toBe('query-scope-style');
    expect($detection->glob)->toBe('app/Models/**');
    expect($detection->title)->toContain('#[Scope] attribute');
    expect($detection->note)->toContain('5/5 scopes');
});

it('detects the scopeXxx() naming style across models', function (): void {
    $base = fixture('conventions/naming-scopes-app');

    $detections = (new QueryScopeStyleDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toHaveCount(1);

    $detection = $detections->first();

    expect($detection->glob)->toBe('app/Models/**');
    expect($detection->title)->toContain('scopeXxx() naming');
    expect($detection->note)->toContain('5/5 scopes');
});

it('stays silent when models declare no scopes', function (): void {
    $base = fixture('conventions/fillable-models-app');

    $detections = (new QueryScopeStyleDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toBeEmpty();
});

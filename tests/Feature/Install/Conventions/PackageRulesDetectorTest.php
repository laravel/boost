<?php

declare(strict_types=1);

use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\Detectors\PackageRulesDetector;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->app->setBasePath(fixture('conventions/package-rules-app'));
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
});

it('surfaces a rule shipped in a package resources/boost/rules directory', function (): void {
    $base = base_path();

    $detections = (new PackageRulesDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toHaveCount(1);

    $detection = $detections->first();

    expect($detection->glob)->toBe('app/Widgets/**');
    expect($detection->title)->toBe('Building Widgets');
    expect($detection->note)
        ->toContain('WidgetManager')
        ->toContain('_Provided by acme/widgets._');
    expect($detection->provenance)->toBe(Detection::packageProvenance('acme/widgets'));
    expect($detection->isInferred())->toBeFalse();
});

it('emits nothing when no package ships boost rules', function (): void {
    $this->app->setBasePath(fixture('conventions/fillable-models-app'));
    $base = base_path();

    $detections = (new PackageRulesDetector)->detect(conventionContext($base, [$base.'/app']));

    expect($detections)->toBeEmpty();
});

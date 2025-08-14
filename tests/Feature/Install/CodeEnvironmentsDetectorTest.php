<?php

declare(strict_types=1);

use Laravel\Boost\Install\CodeEnvironment\Kiro;
use Laravel\Boost\Install\CodeEnvironmentsDetector;

it('includes kiro in available code environments', function () {
    $detector = app(CodeEnvironmentsDetector::class);
    $environments = $detector->getCodeEnvironments();

    expect($environments)->toHaveKey('kiro');
    expect($environments->get('kiro'))->toBeInstanceOf(Kiro::class);
});

it('can detect kiro in project when .kiro directory exists', function () {
    $detector = app(CodeEnvironmentsDetector::class);

    // Since we're in a project with .kiro directory, it should be detected
    $projectEnvironments = $detector->discoverProjectInstalledCodeEnvironments(base_path());

    expect($projectEnvironments)->toContain('kiro');
});

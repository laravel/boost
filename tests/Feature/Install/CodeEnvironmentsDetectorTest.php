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

    // Create a temporary directory with .kiro folder
    $tempDir = sys_get_temp_dir() . '/boost_test_' . uniqid();
    mkdir($tempDir);
    mkdir($tempDir . '/.kiro');

    $projectEnvironments = $detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($projectEnvironments)->toContain('kiro');

    // Cleanup
    rmdir($tempDir . '/.kiro');
    rmdir($tempDir);
});

<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Mcp\Prompts\ThirdPartyPrompt;
use Laravel\Boost\Support\Composer;
use Laravel\Roster\Roster;

test('composer discovers packages with boost guidelines', function (): void {
    $packages = Composer::packagesDirectoriesWithBoostGuidelines();

    expect($packages)->toBeArray();
});

test('blade prompt can be registered with the correct structure', function (): void {
    $testBladePath = sys_get_temp_dir().'/test-registration-'.uniqid().'.blade.php';
    file_put_contents($testBladePath, '# Test Guidelines');

    $roster = app(Roster::class);
    $herd = app(Herd::class);
    $guidelineAssist = new GuidelineAssist($roster, new GuidelineConfig, $herd);

    $prompt = new ThirdPartyPrompt($guidelineAssist, 'test/package', $testBladePath);

    expect($prompt)->toBeInstanceOf(ThirdPartyPrompt::class)
        ->and($prompt->name())->toBe('test/package')
        ->and($prompt->description())->toBe('Guidelines for test/package');

    unlink($testBladePath);
});

test('register third party prompt method creates blade prompt', function (): void {
    $testBladePath = sys_get_temp_dir().'/test-third-party-'.uniqid().'.blade.php';
    file_put_contents($testBladePath, '# Third Party Guidelines');

    $roster = app(Roster::class);
    $herd = app(Herd::class);
    $guidelineAssist = new GuidelineAssist($roster, new GuidelineConfig, $herd);

    $prompt = new ThirdPartyPrompt($guidelineAssist, 'vendor/package', $testBladePath);

    expect($prompt)->toBeInstanceOf(ThirdPartyPrompt::class)
        ->and($prompt->name())->toBe('vendor/package')
        ->and($prompt->description())->toContain('vendor/package');

    $response = $prompt->handle();
    expect($response)->isToolResult()
        ->toolHasNoError();

    unlink($testBladePath);
});

test('discover third party prompts skips non-existent core files', function (): void {
    $testPackagePath = sys_get_temp_dir().'/test-skip-'.uniqid();
    $guidelinesPath = $testPackagePath.'/resources/boost/guidelines';
    mkdir($guidelinesPath, 0777, true);

    $otherFile = $guidelinesPath.'/other.blade.php';
    file_put_contents($otherFile, '# Other File');

    $coreFile = $guidelinesPath.'/core.blade.php';

    expect(file_exists($coreFile))->toBeFalse()
        ->and(file_exists($otherFile))->toBeTrue();

    unlink($otherFile);
    rmdir($guidelinesPath);
    rmdir($testPackagePath.'/resources/boost');
    rmdir($testPackagePath.'/resources');
    rmdir($testPackagePath);
});

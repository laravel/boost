<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Mcp\Resources\ThirdPartyResource;
use Laravel\Boost\Support\Composer;
use Laravel\Roster\Roster;

test('composer discovers packages with boost guidelines', function (): void {
    $packages = Composer::packagesDirectoriesWithBoostGuidelines();

    expect($packages)->toBeArray();
});

test('blade resource can be registered with correct structure', function (): void {
    $testBladePath = sys_get_temp_dir().'/test-resource-registration-'.uniqid().'.blade.php';
    file_put_contents($testBladePath, '# Test Guidelines');

    $roster = app(Roster::class);
    $herd = app(Herd::class);
    $guidelineAssist = new GuidelineAssist($roster, new GuidelineConfig, $herd);

    $resource = new ThirdPartyResource($guidelineAssist, 'test/package', $testBladePath);

    expect($resource)->toBeInstanceOf(ThirdPartyResource::class)
        ->and($resource->uri())->toBe('file://instructions/test/package.md')
        ->and($resource->description())->toBe('Guidelines for test/package')
        ->and($resource->mimeType())->toBe('text/markdown');

    unlink($testBladePath);
});

test('register third party resource method creates blade resource', function (): void {
    $testBladePath = sys_get_temp_dir().'/test-third-party-resource-'.uniqid().'.blade.php';
    file_put_contents($testBladePath, '# Third Party Resource Guidelines');

    $roster = app(Roster::class);
    $herd = app(Herd::class);
    $guidelineAssist = new GuidelineAssist($roster, new GuidelineConfig, $herd);

    $resource = new ThirdPartyResource($guidelineAssist, 'vendor/package', $testBladePath);

    expect($resource)->toBeInstanceOf(ThirdPartyResource::class)
        ->and($resource->uri())->toContain('vendor/package')
        ->and($resource->description())->toContain('vendor/package');

    $response = $resource->handle();
    expect($response)->isToolResult()
        ->toolHasNoError();

    unlink($testBladePath);
});

test('discover third party resources skips non-existent resource files', function (): void {
    $testPackagePath = sys_get_temp_dir().'/test-resource-skip-'.uniqid();
    $guidelinesPath = $testPackagePath.'/resources/boost/guidelines';
    mkdir($guidelinesPath, 0777, true);

    $otherFile = $guidelinesPath.'/other.blade.php';
    file_put_contents($otherFile, '# Other File');

    $resourceFile = $guidelinesPath.'/resource.blade.php';

    expect(file_exists($resourceFile))->toBeFalse()
        ->and(file_exists($otherFile))->toBeTrue();

    unlink($otherFile);
    rmdir($guidelinesPath);
    rmdir($testPackagePath.'/resources/boost');
    rmdir($testPackagePath.'/resources');
    rmdir($testPackagePath);
});

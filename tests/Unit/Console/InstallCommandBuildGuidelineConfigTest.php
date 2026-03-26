<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Support\Config;

beforeEach(function (): void {
    (new Config)->flush();
});

afterEach(function (): void {
    (new Config)->flush();
});

function buildGuidelineConfigWith(Collection $selectedBoostFeatures, Config $config): GuidelineConfig
{
    $command = Mockery::mock(InstallCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $reflect = new ReflectionClass(InstallCommand::class);

    $featuresProperty = $reflect->getProperty('selectedBoostFeatures');
    $featuresProperty->setValue($command, $selectedBoostFeatures);

    $packagesProperty = $reflect->getProperty('selectedThirdPartyPackages');
    $packagesProperty->setValue($command, collect());

    $configProperty = $reflect->getProperty('config');
    $configProperty->setValue($command, $config);

    return $reflect->getMethod('buildGuidelineConfig')->invoke($command);
}

test('hasMcp is true when mcp is in selected boost features', function (): void {
    $config = new Config;

    $guidelineConfig = buildGuidelineConfigWith(
        collect(['guidelines', 'mcp']),
        $config,
    );

    expect($guidelineConfig->hasMcp)->toBeTrue();
});

test('hasMcp is true when mcp is in stored config but not in selected features', function (): void {
    $config = new Config;
    $config->setMcp(true);

    $guidelineConfig = buildGuidelineConfigWith(
        collect(['guidelines']),
        $config,
    );

    expect($guidelineConfig->hasMcp)->toBeTrue();
});

test('hasMcp is false when mcp is neither in selected features nor stored config', function (): void {
    $config = new Config;

    $guidelineConfig = buildGuidelineConfigWith(
        collect(['guidelines']),
        $config,
    );

    expect($guidelineConfig->hasMcp)->toBeFalse();
});

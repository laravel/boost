<?php

use Laravel\Boost\Support\Config;

afterEach(function (): void {
    (new Config)->flush();
});

it('may store and retrieve guidelines status', function (): void {
    $config = new Config;

    expect($config->getGuidelines())->toBeFalse();

    $config->setGuidelines(true);

    expect($config->getGuidelines())->toBeTrue();

    $config->setGuidelines(false);

    expect($config->getGuidelines())->toBeFalse();
});

it('may store and retrieve agents', function (): void {
    $config = new Config;

    expect($config->getAgents())->toBeEmpty();

    $agents = [
        'agent_1',
        'agent_2',
    ];

    $config->setAgents($agents);

    expect($config->getAgents())->toEqual($agents);
});

it('may store and retrieve herd mcp installation status', function (): void {
    $config = new Config;

    expect($config->getHerdMcp())->toBeFalse();

    $config->setHerdMcp(true);

    expect($config->getHerdMcp())->toBeTrue();

    $config->setHerdMcp(false);

    expect($config->getHerdMcp())->toBeFalse();
});

it('may store and retrieve skills status', function (): void {
    $config = new Config;

    expect($config->getSkills())->toBeFalse();

    $config->setSkills(true);

    expect($config->getSkills())->toBeTrue();

    $config->setSkills(false);

    expect($config->getSkills())->toBeFalse();
});

it('may store and retrieve mcp status', function (): void {
    $config = new Config;

    expect($config->getMcp())->toBeFalse();

    $config->setMcp(true);

    expect($config->getMcp())->toBeTrue();

    $config->setMcp(false);

    expect($config->getMcp())->toBeFalse();
});

it('may store and retrieve packages', function (): void {
    $config = new Config;

    expect($config->getPackages())->toBeEmpty();

    $packages = [
        'laravel/fortify',
        'laravel/prism',
    ];

    $config->setPackages($packages);

    expect($config->getPackages())->toEqual($packages);
});

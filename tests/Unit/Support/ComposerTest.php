<?php

use Laravel\Boost\Support\Config;

afterEach(function (): void {
    (new Config)->flush();
});

it('may store and retrieve guidelines', function (): void {
    $config = new Config;

    expect($config->getGuidelines())->toBeEmpty();

    $guidelines = [
        'guideline_1',
        'guideline_2',
    ];

    $config->setGuidelines($guidelines);

    expect($config->getGuidelines())->toEqual($guidelines);
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

it('may store and retrieve editors', function (): void {
    $config = new Config;

    expect($config->getEditors())->toBeEmpty();

    $editors = [
        'editor_1',
        'editor_2',
    ];

    $config->setEditors($editors);

    expect($config->getEditors())->toEqual($editors);
});

it('may store and retrieve herd mcp installation status', function (): void {
    $config = new Config;

    expect($config->getHerdMcp())->toBeFalse();

    $config->setHerdMcp(true);

    expect($config->getHerdMcp())->toBeTrue();

    $config->setHerdMcp(false);

    expect($config->getHerdMcp())->toBeFalse();
});

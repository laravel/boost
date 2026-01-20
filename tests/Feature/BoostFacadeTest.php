<?php

declare(strict_types=1);

use Laravel\Boost\Boost;
use Laravel\Boost\BoostManager;
use Tests\Unit\Install\ExampleAgent;

it('Boost Facade resolves to BoostManager instance', function (): void {
    $instance = Boost::getFacadeRoot();

    expect($instance)->toBeInstanceOf(BoostManager::class);
});

it('Boost Facade registers agents via facade', function (): void {
    Boost::registerAgent('example1', ExampleAgent::class);
    Boost::registerAgent('example2', ExampleAgent::class);
    $registered = Boost::getFacadeRoot()->getAgents();

    expect($registered)->toHaveKey('example1')
        ->and($registered['example1'])->toBe(ExampleAgent::class)
        ->and($registered)->toHaveKey('example2')
        ->and($registered['example2'])->toBe(ExampleAgent::class)
        ->and($registered)->toHaveKey('junie');
});

it('Boost Facade maintains registration state across facade calls', function (): void {
    Boost::registerAgent('persistent', 'Test\Persistent');

    $registered = Boost::getFacadeRoot()->getAgents();

    expect($registered)->toHaveKey('persistent')
        ->and($registered['persistent'])->toBe('Test\Persistent');
});
